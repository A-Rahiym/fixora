<?php

use App\Models\Customer;
use App\Models\Device;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can crud devices with filters', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();

    $created = $this->withToken($token)->postJson('/api/v1/devices', [
        'customer_id' => $customer->id,
        'category' => 'phone',
        'brand' => 'Apple',
        'model' => 'iPhone 14',
    ])->assertCreated()->assertJsonPath('data.device.model', 'iPhone 14');

    $id = $created->json('data.device.id');

    $this->withToken($token)->getJson("/api/v1/devices/{$id}")->assertOk();

    $this->withToken($token)->getJson("/api/v1/devices?customer_id={$customer->id}")->assertOk()->assertJsonFragment(['model' => 'iPhone 14']);

    $this->withToken($token)->getJson('/api/v1/devices?category=laptop')->assertOk()->assertJsonMissing(['model' => 'iPhone 14']);

    $this->withToken($token)->patchJson("/api/v1/devices/{$id}", [
        'color' => 'Midnight',
    ])->assertOk()->assertJsonPath('data.device.color', 'Midnight');

    $this->withToken($token)->deleteJson("/api/v1/devices/{$id}")->assertOk();

    expect(Device::find($id))->toBeNull();
    expect(Device::withTrashed()->find($id))->not->toBeNull();
});
