<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('seeded owner can log in and receive a token', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@fixora.test',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Logged in.')
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]]);
});
