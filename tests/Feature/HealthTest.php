<?php

test('health endpoint returns ok status', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
        ]);
});
