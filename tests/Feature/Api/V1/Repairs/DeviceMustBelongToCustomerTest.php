<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('repair requires the device to belong to the customer', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $otherDevice = Device::factory()->create();

    $this->withToken($token)->postJson('/api/v1/repairs', repairPayload($customer, $otherDevice))
        ->assertUnprocessable();

    expect(Repair::count())->toBe(0);
});
