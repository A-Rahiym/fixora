<?php

use App\Models\Customer;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('deleting a customer with devices is rejected', function () {
    $token = apiToken();
    $customer = Customer::factory()->hasDevices(1)->create();

    $this->withToken($token)->deleteJson("/api/v1/customers/{$customer->id}")->assertUnprocessable();

    expect($customer->fresh())->not->toBeNull();
});
