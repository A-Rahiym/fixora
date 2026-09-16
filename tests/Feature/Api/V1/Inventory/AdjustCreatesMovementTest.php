<?php

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('adjusting stock writes a movement and updates quantity atomically', function () {
    $token = apiToken();
    $item = InventoryItem::factory()->create(['quantity' => 5]);

    $this->withToken($token)->postJson("/api/v1/inventory/{$item->id}/adjust", [
        'quantity_change' => 10,
        'note' => 'Restocked',
    ])->assertOk()->assertJsonPath('data.item.quantity', 15)
        ->assertJsonPath('data.movement.type', 'adjustment_in');

    expect(InventoryMovement::where('inventory_item_id', $item->id)->count())->toBe(1);

    $this->withToken($token)->postJson("/api/v1/inventory/{$item->id}/adjust", [
        'quantity_change' => -3,
    ])->assertOk()->assertJsonPath('data.item.quantity', 12)
        ->assertJsonPath('data.movement.type', 'adjustment_out');

    expect(InventoryMovement::where('inventory_item_id', $item->id)->count())->toBe(2);
    expect(InventoryItem::find($item->id)->quantity)->toBe(12);

    $this->withToken($token)->getJson("/api/v1/inventory/{$item->id}/movements")->assertOk()
        ->assertJsonFragment(['quantity_change' => 10])
        ->assertJsonFragment(['quantity_change' => -3]);
});
