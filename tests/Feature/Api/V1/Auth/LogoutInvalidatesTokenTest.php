<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
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
