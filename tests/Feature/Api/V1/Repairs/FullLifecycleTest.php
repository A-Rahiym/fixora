<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Repair;
use App\Models\RepairStatusHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can run the full repair lifecycle', function () {
    $token = apiToken();
    $customer = Customer::factory()->create();
    $device = Device::factory()->create(['customer_id' => $customer->id]);
    $technician = User::factory()->create(['role_id' => Role::where('name', 'technician')->firstOrFail()->id]);

    $created = $this->withToken($token)->postJson('/api/v1/repairs', repairPayload($customer, $device))
        ->assertCreated()
        ->assertJsonPath('data.repair.status', 'received');

    $id = $created->json('data.repair.id');
    expect($created->json('data.repair.job_number'))->toStartWith('REP-');

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/assign", [
        'assigned_technician_id' => $technician->id,
    ])->assertOk()->assertJsonPath('data.repair.assigned_technician_id', $technician->id);

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/diagnosis", [
        'findings' => 'Loose display connector',
        'recommended_action' => 'Reseat and test',
    ])->assertCreated();

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/notes", [
        'note' => 'Customer called for an update',
    ])->assertCreated();

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/status", [
        'status' => 'diagnosing',
    ])->assertOk()->assertJsonPath('data.repair.status', 'diagnosing');

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/approve")
        ->assertOk()->assertJsonPath('data.repair.status', 'approved');

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/complete")
        ->assertOk()->assertJsonPath('data.repair.status', 'ready_for_collection');

    $this->withToken($token)->postJson("/api/v1/repairs/{$id}/collect")
        ->assertOk()
        ->assertJsonPath('data.repair.status', 'collected');

    $repair = Repair::findOrFail($id);
    expect($repair->collected_at)->not->toBeNull();
    expect($repair->warranty_expires_at)->not->toBeNull();

    // received (create) + diagnosing + approved + ready_for_collection + collected
    expect(RepairStatusHistory::where('repair_id', $id)->count())->toBe(5);
});
