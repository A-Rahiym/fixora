<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function rolesToken(string $role = 'owner'): string
{
    $roleModel = Role::where('name', $role)->firstOrFail();
    $user = User::factory()->create(['role_id' => $roleModel->id]);

    return $user->createToken('api')->plainTextToken;
}

test('owner can list roles and permissions', function () {
    $this->withToken(rolesToken())->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonFragment(['name' => 'owner'])
        ->assertJsonFragment(['name' => 'technician']);

    $this->withToken(rolesToken())->getJson('/api/v1/permissions')
        ->assertOk()
        ->assertJsonStructure(['data' => ['permissions']]);
});

test('owner can update a role permission set', function () {
    $role = Role::where('name', 'cashier')->firstOrFail();

    $this->withToken(rolesToken())->patchJson("/api/v1/roles/{$role->id}", [
        'permissions' => ['repairs.view', 'inventory.view'],
    ])->assertOk();

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['inventory.view', 'repairs.view']);
});

test('technician is blocked from roles endpoints', function () {
    $this->withToken(rolesToken('technician'))->getJson('/api/v1/roles')->assertForbidden();
    $this->withToken(rolesToken('technician'))->getJson('/api/v1/permissions')->assertForbidden();
});

test('unauthenticated requests are rejected', function () {
    $this->getJson('/api/v1/staff')->assertUnauthorized();
    $this->getJson('/api/v1/roles')->assertUnauthorized();
});
