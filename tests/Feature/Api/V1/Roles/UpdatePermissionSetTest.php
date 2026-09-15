<?php

use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner can update a role permission set', function () {
    $role = Role::where('name', 'cashier')->firstOrFail();

    $this->withToken(apiToken())->patchJson("/api/v1/roles/{$role->id}", [
        'permissions' => ['repairs.view', 'inventory.view'],
    ])->assertOk();

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['inventory.view', 'repairs.view']);
});
