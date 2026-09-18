<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('pipeline reports per-technician load and overall utilization', function () {
    $token = apiToken();
    $technicianRole = Role::where('name', 'technician')->firstOrFail();

    $busy = User::factory()->create(['role_id' => $technicianRole->id, 'name' => 'Busy Tech']);
    User::factory()->create(['role_id' => $technicianRole->id, 'name' => 'Idle Tech']);

    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);

    Repair::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'device_id' => $device->id,
        'status' => 'in_repair',
        'assigned_technician_id' => $busy->id,
    ]);

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/repair-pipeline');

    $response->assertOk()->assertJsonPath('message', 'OK');

    $capacity = $response->json('data.capacity');

    // 3 active jobs across 2 techs with capacity 15 each.
    expect($capacity['utilization_pct'])->toBe(10)
        ->and($capacity['entries'][0])->toMatchArray([
            'technician' => 'Busy Tech',
            'role' => 'Technician',
            'load_pct' => 20,
            'jobs' => 3,
        ])
        ->and($capacity['entries'][1])->toMatchArray(['technician' => 'Idle Tech', 'jobs' => 0]);
});

test('pipeline is empty when no technicians exist', function () {
    $token = apiToken();

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/repair-pipeline');

    $response->assertOk();

    expect($response->json('data.capacity'))->toBe(['utilization_pct' => 0, 'entries' => []]);
});

test('pipeline requires dashboard permission', function () {
    $rolelessToken = User::factory()->create(['role_id' => null])->createToken('api')->plainTextToken;

    $this->withToken($rolelessToken)->getJson('/api/v1/dashboard/repair-pipeline')->assertForbidden();
});
