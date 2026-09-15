<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('repair list supports status filter and search', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);

    $id = $this->withToken($token)->postJson('/api/v1/repairs', repairPayload($customer, $device))
        ->assertCreated()->json('data.repair.id');

    $jobNumber = Repair::findOrFail($id)->job_number;

    $this->withToken($token)->getJson('/api/v1/repairs?status=received')->assertOk()->assertJsonFragment(['job_number' => $jobNumber]);
    $this->withToken($token)->getJson('/api/v1/repairs?status=in_repair')->assertOk()->assertJsonMissing(['job_number' => $jobNumber]);
    $this->withToken($token)->getJson("/api/v1/repairs?search={$jobNumber}")->assertOk()->assertJsonFragment(['job_number' => $jobNumber]);
});
