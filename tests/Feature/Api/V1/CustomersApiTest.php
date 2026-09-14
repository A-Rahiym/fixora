<?php

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function customersToken(string $role = 'owner'): string
{
    $roleModel = Role::where('name', $role)->firstOrFail();
    $user = User::factory()->create(['role_id' => $roleModel->id]);

    return $user->createToken('api')->plainTextToken;
}

test('owner can crud customers with pagination and search', function () {
    $token = customersToken();

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

test('creating a customer with an existing phone warns instead of duplicating', function () {
    $token = customersToken();
    $existing = Customer::factory()->create(['phone' => '+15550100']);

    $response = $this->withToken($token)->postJson('/api/v1/customers', [
        'name' => 'Possible Duplicate',
        'phone' => '+1 (555) 0100',
    ])->assertConflict();

    expect($response->json('errors.customer.id'))->toBe($existing->id);
    expect(Customer::where('name', 'Possible Duplicate')->exists())->toBeFalse();
});

test('updating a customer to another customer phone warns', function () {
    $token = customersToken();
    Customer::factory()->create(['phone' => '5550100']);
    $other = Customer::factory()->create(['phone' => '5550200']);

    $this->withToken($token)->patchJson("/api/v1/customers/{$other->id}", [
        'phone' => '555 0100',
    ])->assertConflict();
});

test('deleting a customer with devices is rejected', function () {
    $token = customersToken();
    $customer = Customer::factory()->hasDevices(1)->create();

    $this->withToken($token)->deleteJson("/api/v1/customers/{$customer->id}")->assertUnprocessable();

    expect($customer->fresh())->not->toBeNull();
});

test('technician is blocked from managing customers', function () {
    $this->withToken(customersToken('technician'))->getJson('/api/v1/customers')->assertOk();
    $this->withToken(customersToken('technician'))->postJson('/api/v1/customers', [])->assertForbidden();
});

test('customer devices list returns scoped devices', function () {
    $token = customersToken();
    $customer = Customer::factory()->hasDevices(2)->create();
    Customer::factory()->hasDevices(1)->create();

    $this->withToken($token)->getJson("/api/v1/customers/{$customer->id}/devices")
        ->assertOk()
        ->assertJsonCount(2, 'data.data');
});
