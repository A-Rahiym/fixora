<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('staff creation rejects duplicate email', function () {
    $token = apiToken();
    $existing = User::factory()->create();

    $this->withToken($token)->postJson('/api/v1/staff', [
        'name' => 'Dupe',
        'email' => $existing->email,
        'password' => 'Sup3r-secret-password!',
        'password_confirmation' => 'Sup3r-secret-password!',
    ])->assertUnprocessable();
});
