<?php

test('health endpoint returns ok status', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertExactJson([
            'data' => ['status' => 'ok'],
            'message' => 'OK',
        ]);
});

test('versioned health endpoint returns ok status', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson([
            'data' => ['status' => 'ok', 'version' => 'v1'],
            'message' => 'OK',
        ]);
});
