<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('technician is blocked from managing customers', function () {
    $this->withToken(apiToken('technician'))->getJson('/api/v1/customers')->assertOk();
    $this->withToken(apiToken('technician'))->postJson('/api/v1/customers', [])->assertForbidden();
});
