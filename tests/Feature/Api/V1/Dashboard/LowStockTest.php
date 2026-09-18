<?php

use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('low stock returns only items at or below threshold', function () {
    $token = apiToken();

    $low = InventoryItem::factory()->create([
        'name' => 'iPhone 13 Pro Screen',
        'sku' => 'SCR-IP13P',
        'quantity' => 2,
        'low_stock_threshold' => 5,
    ]);
    InventoryItem::factory()->create(['quantity' => 50, 'low_stock_threshold' => 5]);

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/low-stock');

    $response->assertOk()->assertJsonPath('message', 'OK');

    expect($response->json('data.low_stock_items'))->toBe([[
        'id' => $low->id,
        'name' => 'iPhone 13 Pro Screen',
        'sku' => 'SCR-IP13P',
        'quantity' => 2,
        'threshold' => 5,
    ]]);
});

test('low stock requires dashboard permission', function () {
    $rolelessToken = User::factory()->create(['role_id' => null])->createToken('api')->plainTextToken;

    $this->withToken($rolelessToken)->getJson('/api/v1/dashboard/low-stock')->assertForbidden();
});
