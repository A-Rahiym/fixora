<?php

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can crud inventory items with filters', function () {
    $token = apiToken();
    $category = InventoryCategory::factory()->create();

    $created = $this->withToken($token)->postJson('/api/v1/inventory', [
        ...inventoryPayload($category),
        'opening_quantity' => 10,
    ])->assertCreated();

    $id = $created->json('data.item.id');
    expect($created->json('data.item.quantity'))->toBe(10);

    $this->withToken($token)->getJson("/api/v1/inventory/{$id}")->assertOk();

    $this->withToken($token)->getJson('/api/v1/inventory?search=iPhone')->assertOk()->assertJsonFragment(['name' => 'iPhone 14 Display']);

    $this->withToken($token)->getJson("/api/v1/inventory?category_id={$category->id}")->assertOk()->assertJsonFragment(['name' => 'iPhone 14 Display']);

    $this->withToken($token)->patchJson("/api/v1/inventory/{$id}", [
        'low_stock_threshold' => 12,
    ])->assertOk()->assertJsonPath('data.item.low_stock_threshold', 12);

    $this->withToken($token)->deleteJson("/api/v1/inventory/{$id}")->assertOk();

    expect(InventoryItem::find($id))->toBeNull();
    expect(InventoryItem::withTrashed()->find($id))->not->toBeNull();
});
