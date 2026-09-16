<?php

use App\Models\InventoryItem;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('inventory store and adjust validation rejects bad input', function () {
    $token = apiToken();

    $this->withToken($token)->postJson('/api/v1/inventory', [])->assertStatus(422)
        ->assertJsonValidationErrors(['category_id', 'sku', 'name', 'unit_cost', 'unit_price']);

    $existing = InventoryItem::factory()->create();

    $this->withToken($token)->postJson('/api/v1/inventory', [
        ...inventoryPayload(),
        'sku' => $existing->sku,
    ])->assertStatus(422)->assertJsonValidationErrors(['sku']);

    $this->withToken($token)->postJson('/api/v1/inventory', [
        ...inventoryPayload(),
        'unit_cost' => -5,
    ])->assertStatus(422)->assertJsonValidationErrors(['unit_cost']);

    $this->withToken($token)->postJson("/api/v1/inventory/{$existing->id}/adjust", [
        'quantity_change' => 0,
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_change']);
});
