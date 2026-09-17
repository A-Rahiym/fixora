<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Log in through the real endpoint. Returns the login response plus the
 * raw wire cookie (opaque while EncryptCookies guards the api group).
 *
 * @return array{0: TestResponse, 1: Cookie}
 */
function loginWithCookie(TestCase $testCase, string $email, string $password = 'password'): array
{
    $login = $testCase->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => $password,
    ])->assertOk();

    /** @var Cookie|null $cookie */
    $cookie = collect($login->headers->getCookies())
        ->first(fn (Cookie $c) => $c->getName() === 'fixora_token');

    expect($cookie)->not->toBeNull();

    return [$login, $cookie];
}

test('login sets an opaque httponly cookie and returns the token in json', function () {
    [$login, $cookie] = loginWithCookie($this, 'owner@fixora.test');

    expect($cookie->isHttpOnly())->toBeTrue()
        ->and(strtolower((string) $cookie->getSameSite()))->toBe('lax')
        ->and($login->json('data.token'))->toBeString()->not->toBe('')
        // The wire value is encrypted, never the raw token.
        ->and((string) $cookie->getValue())->not->toBe($login->json('data.token'));
});

test('cookie-only request authenticates without an authorization header', function () {
    $user = User::factory()->create();

    [$login] = loginWithCookie($this, $user->email);

    auth()->forgetGuards();

    // withCookie encrypts like production: plaintext in, opaque on the wire.
    $this->withCookie('fixora_token', $login->json('data.token'))
        ->withCredentials()
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', $user->email);
});

test('bearer header takes priority when both header and cookie are sent', function () {
    $headerUser = User::factory()->create();
    $headerToken = $headerUser->createToken('api')->plainTextToken;

    $cookieUser = User::factory()->create();
    [$cookieLogin] = loginWithCookie($this, $cookieUser->email);

    auth()->forgetGuards();

    $this->withToken($headerToken)
        ->withCookie('fixora_token', $cookieLogin->json('data.token'))
        ->withCredentials()
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', $headerUser->email);
});

test('tampered cookie value is treated as unauthenticated', function () {
    User::factory()->create();

    auth()->forgetGuards();

    // Raw garbage bypasses client-side encryption, so server-side
    // decryption fails and the cookie resolves to null.
    $this->withUnencryptedCookie('fixora_token', 'not-a-valid-payload')
        ->withCredentials()
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

test('logout revokes the cookie token and clears the cookie', function () {
    $user = User::factory()->create();

    [$login] = loginWithCookie($this, $user->email);
    $token = $login->json('data.token');

    $this->withCookie('fixora_token', $token)
        ->withCredentials()
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertCookieExpired('fixora_token');

    auth()->forgetGuards();

    $this->withCookie('fixora_token', $token)
        ->withCredentials()
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
