<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can list, create, view and update staff', function () {
    $token = apiToken();

    $this->withToken($token)->getJson('/api/v1/staff')->assertOk();

    $created = $this->withToken($token)->postJson('/api/v1/staff', [
        'name' => 'New Tech',
        'email' => 'tech@example.com',
        'password' => 'Sup3r-secret-password!',
        'password_confirmation' => 'Sup3r-secret-password!',
    ])->assertCreated()->assertJsonPath('data.user.email', 'tech@example.com');

    $id = $created->json('data.user.id');

    $this->withToken($token)->getJson("/api/v1/staff/{$id}")->assertOk();

    $this->withToken($token)->patchJson("/api/v1/staff/{$id}", [
        'name' => 'Renamed Tech',
    ])->assertOk()->assertJsonPath('data.user.name', 'Renamed Tech');
});
