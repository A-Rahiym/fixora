<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('login rejects invalid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@fixora.test',
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});
