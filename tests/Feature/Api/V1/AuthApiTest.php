<?php

use App\Models\Role;
use App\Models\User;
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

test('login rejects invalid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@fixora.test',
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});

test('login rejects disabled staff even with correct credentials', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertForbidden();
});

test('logout invalidates the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    // Guards resolve once per app instance; forget them so the next
    // request re-resolves the (now deleted) token like production would.
    auth()->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
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

test('login requires email and password', function () {
    $this->postJson('/api/v1/auth/login', [])->assertUnprocessable();
});
