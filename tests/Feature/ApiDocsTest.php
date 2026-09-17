<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    Gate::define('viewApiDocs', fn (?User $user) => true);
});

test('openapi json documents versioned api with bearer security', function () {
    $response = $this->getJson('/docs/api.json');

    $response->assertOk();

    $document = $response->json();

    expect($document['openapi'])->toStartWith('3.1.')
        ->and($document['info']['version'])->toBe('v1')
        ->and($document['paths'])->toHaveKeys(['/v1/auth/login', '/v1/repairs', '/v1/customers'])
        ->and($document['components']['securitySchemes'])->toHaveKeys(['http', 'apiKey'])
        ->and($document['components']['securitySchemes']['apiKey'])->toMatchArray([
            'type' => 'apiKey',
            'in' => 'cookie',
            'name' => 'fixora_token',
        ]);
});

test('public auth routes carry no security while protected routes inherit bearer auth', function () {
    $document = $this->getJson('/docs/api.json')->json();

    expect($document['paths']['/v1/auth/login']['post']['security'])->toBe([])
        ->and($document['security'])->toContain(['http' => []])
        ->and($document['security'])->toContain(['apiKey' => []]);
});
