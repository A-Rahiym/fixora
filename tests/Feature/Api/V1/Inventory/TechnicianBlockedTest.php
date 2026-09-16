<?php

use App\Models\InventoryItem;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('technician is blocked from inventory writes', function () {
    $token = apiToken('technician');
    $item = InventoryItem::factory()->create(['quantity' => 5]);

    $this->withToken($token)->postJson('/api/v1/inventory', inventoryPayload())->assertForbidden();
    $this->withToken($token)->postJson("/api/v1/inventory/{$item->id}/adjust", ['quantity_change' => 1])->assertForbidden();
    $this->withToken($token)->getJson('/api/v1/inventory')->assertOk();
});
