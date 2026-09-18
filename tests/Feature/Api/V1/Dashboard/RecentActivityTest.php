<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('recent activity returns latest jobs with canonical fields', function () {
    $token = apiToken();
    $customer = Customer::factory()->create(['name' => 'Michael Chen']);
    $device = Device::factory()->create(['customer_id' => $customer->id, 'brand' => 'Samsung', 'model' => 'S22 Ultra']);

    Repair::factory()->create([
        'customer_id' => $customer->id,
        'device_id' => $device->id,
        'status' => 'in_repair',
        'priority' => 'high',
        'reported_problem' => 'Screen Replacement',
    ]);

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/recent-activity');

    $response->assertOk()->assertJsonPath('message', 'OK');

    $job = $response->json('data.recent_jobs.0');

    expect($job['customer'])->toBe('Michael Chen')
        ->and($job['device'])->toBe('Samsung S22 Ultra')
        ->and($job['issue'])->toBe('Screen Replacement')
        ->and($job['status'])->toBe('in_repair')
        ->and($job['priority'])->toBe('high')
        ->and($job['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
});

test('recent activity honours the limit parameter', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);

    Repair::factory()->count(3)->create(['customer_id' => $customer->id, 'device_id' => $device->id]);

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/recent-activity?limit=2');

    $response->assertOk();

    expect($response->json('data.recent_jobs'))->toHaveCount(2);
});

test('recent activity requires dashboard permission', function () {
    $rolelessToken = User::factory()->create(['role_id' => null])->createToken('api')->plainTextToken;

    $this->withToken($rolelessToken)->getJson('/api/v1/dashboard/recent-activity')->assertForbidden();
});
