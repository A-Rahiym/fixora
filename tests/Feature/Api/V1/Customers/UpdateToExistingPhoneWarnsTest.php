<?php

use App\Models\Customer;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('updating a customer to another customer phone warns', function () {
    $token = apiToken();
    Customer::factory()->create(['phone' => '5550100']);
    $other = Customer::factory()->create(['phone' => '5550200']);

    $this->withToken($token)->patchJson("/api/v1/customers/{$other->id}", [
        'phone' => '555 0100',
    ])->assertConflict();
});
