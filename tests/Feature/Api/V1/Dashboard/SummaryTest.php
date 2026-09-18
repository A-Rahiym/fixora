<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('summary returns metrics, revenue series and device mix', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $phone = Device::factory()->create(['customer_id' => $customer->id, 'category' => 'phone', 'brand' => 'Samsung', 'model' => 'S22 Ultra']);

    Repair::factory()->create([
        'customer_id' => $customer->id,
        'device_id' => $phone->id,
        'status' => 'in_repair',
    ]);
    Repair::factory()->create([
        'customer_id' => $customer->id,
        'device_id' => $phone->id,
        'status' => 'collected',
        'final_cost' => '250.00',
        'collected_at' => now(),
    ]);

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/summary');

    $response->assertOk()->assertJsonPath('message', 'OK');

    $document = $response->json('data');

    expect($document['metrics'])->toHaveCount(4)
        ->and(array_column($document['metrics'], 'id'))->toBe(['active-jobs', 'turnaround', 'revenue', 'efficiency'])
        ->and($document['metrics'][0]['value'])->toBe('1')
        ->and($document['metrics'][2]['value'])->toBe('250')
        ->and($document['revenue']['current_total'])->toBe(250)
        ->and($document['revenue']['points'])->toHaveCount(7)
        ->and($document['device_mix'][0])->toMatchArray(['category' => 'Smartphone', 'count' => 1, 'pct' => 100]);
});

test('summary respects a custom from/to range', function () {
    $token = apiToken();

    $response = $this->withToken($token)->getJson('/api/v1/dashboard/summary?from='.now()->subDays(2)->toDateString().'&to='.now()->toDateString());

    $response->assertOk();

    expect($response->json('data.revenue.points'))->toHaveCount(3);
});

test('summary rejects an inverted range', function () {
    $token = apiToken();

    $this->withToken($token)->getJson('/api/v1/dashboard/summary?from='.now()->toDateString().'&to='.now()->subDays(2)->toDateString())
        ->assertUnprocessable();
});

test('summary requires authentication and dashboard permission', function () {
    $this->getJson('/api/v1/dashboard/summary')->assertUnauthorized();

    $rolelessToken = User::factory()->create(['role_id' => null])->createToken('api')->plainTextToken;

    $this->withToken($rolelessToken)->getJson('/api/v1/dashboard/summary')->assertForbidden();
});
