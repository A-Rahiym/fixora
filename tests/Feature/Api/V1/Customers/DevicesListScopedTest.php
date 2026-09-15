<?php

use App\Models\Customer;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('customer devices list returns scoped devices', function () {
    $token = apiToken();
    $customer = Customer::factory()->hasDevices(2)->create();
    Customer::factory()->hasDevices(1)->create();

    $this->withToken($token)->getJson("/api/v1/customers/{$customer->id}/devices")
        ->assertOk()
        ->assertJsonCount(2, 'data.data');
});
