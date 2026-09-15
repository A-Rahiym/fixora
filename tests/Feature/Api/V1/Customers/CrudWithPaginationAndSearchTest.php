<?php

use App\Models\Customer;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can crud customers with pagination and search', function () {
    $token = apiToken();

    Customer::factory()->count(16)->create(['name' => 'Paginated Customer']);

    $this->withToken($token)->getJson('/api/v1/customers')->assertOk()->assertJsonPath('data.per_page', 15);

    $this->withToken($token)->getJson('/api/v1/customers?search=Paginated')->assertOk()->assertJsonFragment(['name' => 'Paginated Customer']);

    $created = $this->withToken($token)->postJson('/api/v1/customers', [
        'name' => 'Jane Doe',
        'phone' => '+1 555-0100',
    ])->assertCreated()->assertJsonPath('data.customer.name', 'Jane Doe');

    $id = $created->json('data.customer.id');

    $this->withToken($token)->getJson("/api/v1/customers/{$id}")->assertOk();

    $this->withToken($token)->patchJson("/api/v1/customers/{$id}", [
        'address' => '12 Repair Lane',
    ])->assertOk()->assertJsonPath('data.customer.address', '12 Repair Lane');
});
