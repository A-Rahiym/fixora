<?php

use App\Models\InventoryItem;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('low-stock view reflects quantity and threshold changes', function () {
    $token = apiToken();
    $low = InventoryItem::factory()->create(['quantity' => 3, 'low_stock_threshold' => 5]);
    $healthy = InventoryItem::factory()->create(['quantity' => 20, 'low_stock_threshold' => 5]);

    $this->withToken($token)->getJson('/api/v1/inventory/low-stock')->assertOk()
        ->assertJsonFragment(['id' => $low->id])
        ->assertJsonMissing(['id' => $healthy->id]);

    $this->withToken($token)->postJson("/api/v1/inventory/{$low->id}/adjust", [
        'quantity_change' => 10,
    ])->assertOk();

    $this->withToken($token)->getJson('/api/v1/inventory/low-stock')->assertOk()
        ->assertJsonMissing(['id' => $low->id]);

    $this->withToken($token)->patchJson("/api/v1/inventory/{$healthy->id}", [
        'low_stock_threshold' => 25,
    ])->assertOk();

    $this->withToken($token)->getJson('/api/v1/inventory/low-stock')->assertOk()
        ->assertJsonFragment(['id' => $healthy->id]);
});
