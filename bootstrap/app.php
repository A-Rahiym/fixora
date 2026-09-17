<?php

use App\Http\Middleware\AuthenticateViaCookieOrBearer;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'permission' => EnsurePermission::class,
            'token.cookie' => AuthenticateViaCookieOrBearer::class,
        ]);

        // The bridge must run before auth:sanctum resolves the user, but
        // Laravel sorts route middleware by priority (auth matches via its
        // AuthenticatesRequests contract) — pin our order explicitly.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, AuthenticateViaCookieOrBearer::class);

        // Encrypt API cookies so fixora_token travels opaque on the wire.
        // Decrypt failures resolve to null (never 500), and EncryptCookies
        // already sits ahead of auth in the priority list.
        $middleware->api(prepend: [
            EncryptCookies::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
