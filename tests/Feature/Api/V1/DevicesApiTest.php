<?php

use App\Models\Customer;
use App\Models\Device;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function devicesToken(string $role = 'owner'): string
{
    $roleModel = Role::where('name', $role)->firstOrFail();
    $user = User::factory()->create(['role_id' => $roleModel->id]);

    return $user->createToken('api')->plainTextToken;
}

test('owner can crud devices with filters', function () {
    $token = devicesToken();
    $customer = Customer::factory()->create();

    $created = $this->withToken($token)->postJson('/api/v1/devices', [
        'customer_id' => $customer->id,
        'category' => 'phone',
        'brand' => 'Apple',
        'model' => 'iPhone 14',
    ])->assertCreated()->assertJsonPath('data.device.model', 'iPhone 14');

    $id = $created->json('data.device.id');

    $this->withToken($token)->getJson("/api/v1/devices/{$id}")->assertOk();

    $this->withToken($token)->getJson("/api/v1/devices?customer_id={$customer->id}")->assertOk()->assertJsonFragment(['model' => 'iPhone 14']);

    $this->withToken($token)->getJson('/api/v1/devices?category=laptop')->assertOk()->assertJsonMissing(['model' => 'iPhone 14']);

    $this->withToken($token)->patchJson("/api/v1/devices/{$id}", [
        'color' => 'Midnight',
    ])->assertOk()->assertJsonPath('data.device.color', 'Midnight');

    $this->withToken($token)->deleteJson("/api/v1/devices/{$id}")->assertOk();

    expect(Device::find($id))->toBeNull();
    expect(Device::withTrashed()->find($id))->not->toBeNull();
});

test('device requires an existing customer', function () {
    $token = devicesToken();

    $this->withToken($token)->postJson('/api/v1/devices', [
        'customer_id' => 999999,
        'category' => 'phone',
        'brand' => 'Apple',
        'model' => 'iPhone 14',
    ])->assertUnprocessable();
});

test('device repairs endpoint is stubbed until phase 3', function () {
    $token = devicesToken();
    $device = Device::factory()->create();

    $this->withToken($token)->getJson("/api/v1/devices/{$device->id}/repairs")->assertOk()->assertJsonPath('data', []);
});

test('technician is blocked from managing devices', function () {
    $this->withToken(devicesToken('technician'))->getJson('/api/v1/devices')->assertOk();
    $this->withToken(devicesToken('technician'))->postJson('/api/v1/devices', [])->assertForbidden();
});
