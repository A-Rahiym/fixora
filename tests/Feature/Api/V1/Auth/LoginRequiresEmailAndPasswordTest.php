<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('login requires email and password', function () {
    $this->postJson('/api/v1/auth/login', [])->assertUnprocessable();
});
