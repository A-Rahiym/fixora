<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('technician is blocked from managing devices', function () {
    $this->withToken(apiToken('technician'))->getJson('/api/v1/devices')->assertOk();
    $this->withToken(apiToken('technician'))->postJson('/api/v1/devices', [])->assertForbidden();
});
