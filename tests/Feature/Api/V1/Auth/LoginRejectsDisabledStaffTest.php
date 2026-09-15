<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('login rejects disabled staff even with correct credentials', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertForbidden();
});
