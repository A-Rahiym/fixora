<?php

use App\Models\Customer;
use App\Models\Device;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('deleting a device or customer with repairs is rejected', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);

    $this->withToken($token)->postJson('/api/v1/repairs', repairPayload($customer, $device))->assertCreated();

    $this->withToken($token)->deleteJson("/api/v1/devices/{$device->id}")->assertUnprocessable();
    $this->withToken($token)->deleteJson("/api/v1/customers/{$customer->id}")->assertUnprocessable();
});
