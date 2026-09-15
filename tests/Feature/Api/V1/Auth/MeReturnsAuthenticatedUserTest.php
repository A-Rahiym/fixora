<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('me returns the authenticated user with role', function () {
    $ownerRole = Role::where('name', 'owner')->firstOrFail();
    $user = User::factory()->create(['role_id' => $ownerRole->id]);
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.user.role.name', 'owner');
});
