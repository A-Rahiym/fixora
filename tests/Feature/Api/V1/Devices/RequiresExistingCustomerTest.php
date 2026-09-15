<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('device requires an existing customer', function () {
    $token = apiToken();

    $this->withToken($token)->postJson('/api/v1/devices', [
        'customer_id' => 999999,
        'category' => 'phone',
        'brand' => 'Apple',
        'model' => 'iPhone 14',
    ])->assertUnprocessable();
});
