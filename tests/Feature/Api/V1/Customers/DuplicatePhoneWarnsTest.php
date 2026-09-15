<?php

use App\Models\Customer;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('creating a customer with an existing phone warns instead of duplicating', function () {
    $token = apiToken();
    $existing = Customer::factory()->create(['phone' => '+15550100']);

    $response = $this->withToken($token)->postJson('/api/v1/customers', [
        'name' => 'Possible Duplicate',
        'phone' => '+1 (555) 0100',
    ])->assertConflict();

    expect($response->json('errors.customer.id'))->toBe($existing->id);
    expect(Customer::where('name', 'Possible Duplicate')->exists())->toBeFalse();
});
