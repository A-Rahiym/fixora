<?php

use App\Models\Customer;
use App\Models\Device;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('device repairs endpoint returns scoped repairs', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);
    $other = Device::factory()->create();

    $this->withToken($token)->postJson('/api/v1/repairs', repairPayload($customer, $device))->assertCreated();

    $this->withToken($token)->getJson("/api/v1/devices/{$device->id}/repairs")
        ->assertOk()
        ->assertJsonCount(1, 'data.data');

    $this->withToken($token)->getJson("/api/v1/devices/{$other->id}/repairs")
        ->assertOk()
        ->assertJsonCount(0, 'data.data');
});
