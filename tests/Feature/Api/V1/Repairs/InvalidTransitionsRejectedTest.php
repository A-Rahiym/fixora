<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('invalid status transitions are rejected', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);

    $id = $this->withToken($token)->postJson('/api/v1/repairs', repairPayload($customer, $device))
        ->assertCreated()->json('data.repair.id');

    // Same-status transition is a no-op and rejected.
    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/status", [
        'status' => 'received',
    ])->assertUnprocessable();

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/collect")->assertOk();

    // Terminal states cannot be left.
    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/status", [
        'status' => 'received',
    ])->assertUnprocessable();

    expect(Repair::findOrFail($id)->status)->toBe('collected');
});
