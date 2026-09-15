<?php

use App\Models\Device;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('device repairs endpoint returns scoped repairs', function () {
    $token = apiToken();
    $device = Device::factory()->create();

    $this->withToken($token)->getJson("/api/v1/devices/{$device->id}/repairs")->assertOk()->assertJsonCount(0, 'data.data');
});
