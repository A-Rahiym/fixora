<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('disabling staff flips is_active and revokes tokens', function () {
    $ownerToken = apiToken();
    $staff = User::factory()->create();
    $staffToken = $staff->createToken('api')->plainTextToken;

    $this->withToken($ownerToken)->deleteJson("/api/v1/staff/{$staff->id}")->assertOk();

    expect($staff->fresh()->is_active)->toBeFalse();

    // Guards resolve once per app instance; forget them so the next
    // request re-resolves the (now revoked) token like production would.
    auth()->forgetGuards();

    $this->withToken($staffToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
});
