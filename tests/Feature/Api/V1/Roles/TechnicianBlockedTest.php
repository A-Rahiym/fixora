<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('technician is blocked from roles endpoints', function () {
    $this->withToken(apiToken('technician'))->getJson('/api/v1/roles')->assertForbidden();
    $this->withToken(apiToken('technician'))->getJson('/api/v1/permissions')->assertForbidden();
});
