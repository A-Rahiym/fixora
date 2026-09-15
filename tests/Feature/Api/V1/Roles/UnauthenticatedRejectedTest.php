<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('unauthenticated requests are rejected', function () {
    $this->getJson('/api/v1/staff')->assertUnauthorized();
    $this->getJson('/api/v1/roles')->assertUnauthorized();
});
