<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function staffToken(string $role = 'owner'): string
{
    $roleModel = Role::where('name', $role)->firstOrFail();
    $user = User::factory()->create(['role_id' => $roleModel->id]);

    return $user->createToken('api')->plainTextToken;
}

test('technician is blocked from staff management', function () {
    $this->withToken(staffToken('technician'))->getJson('/api/v1/staff')->assertForbidden();
    $this->withToken(staffToken('technician'))->postJson('/api/v1/staff', [])->assertForbidden();
});

test('owner can list, create, view and update staff', function () {
    $token = staffToken();

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

test('staff creation rejects duplicate email', function () {
    $token = staffToken();
    $existing = User::factory()->create();

    $this->withToken($token)->postJson('/api/v1/staff', [
        'name' => 'Dupe',
        'email' => $existing->email,
        'password' => 'Sup3r-secret-password!',
        'password_confirmation' => 'Sup3r-secret-password!',
    ])->assertUnprocessable();
});

test('disabling staff flips is_active and revokes tokens', function () {
    $ownerToken = staffToken();
    $staff = User::factory()->create();
    $staffToken = $staff->createToken('api')->plainTextToken;

    $this->withToken($ownerToken)->deleteJson("/api/v1/staff/{$staff->id}")->assertOk();

    expect($staff->fresh()->is_active)->toBeFalse();

    // Guards resolve once per app instance; forget them so the next
    // request re-resolves the (now revoked) token like production would.
    auth()->forgetGuards();

    $this->withToken($staffToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
});
