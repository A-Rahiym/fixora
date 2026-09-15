<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can list roles and permissions', function () {
    $this->withToken(apiToken())->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonFragment(['name' => 'owner'])
        ->assertJsonFragment(['name' => 'technician']);

    $this->withToken(apiToken())->getJson('/api/v1/permissions')
        ->assertOk()
        ->assertJsonStructure(['data' => ['permissions']]);
});
