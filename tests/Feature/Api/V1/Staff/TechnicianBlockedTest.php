<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('technician is blocked from staff management', function () {
    $this->withToken(apiToken('technician'))->getJson('/api/v1/staff')->assertForbidden();
    $this->withToken(apiToken('technician'))->postJson('/api/v1/staff', [])->assertForbidden();
});
