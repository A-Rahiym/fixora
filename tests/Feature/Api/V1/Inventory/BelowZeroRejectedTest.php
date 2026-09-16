<?php

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('adjusting stock below zero is rejected with no writes', function () {
    $token = apiToken();
    $item = InventoryItem::factory()->create(['quantity' => 2]);

    $this->withToken($token)->postJson("/api/v1/inventory/{$item->id}/adjust", [
        'quantity_change' => -5,
    ])->assertStatus(422);

    expect(InventoryMovement::where('inventory_item_id', $item->id)->count())->toBe(0);
    expect(InventoryItem::find($item->id)->quantity)->toBe(2);
});
