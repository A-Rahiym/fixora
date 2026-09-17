<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Minimal hybrid credential bridge for the API.
 *
 * Header-first, cookie-fallback: when no explicit `Authorization: Bearer`
 * header is present, the `fixora_token` HttpOnly cookie value is injected
 * as the Bearer token so downstream `auth:sanctum` sees no difference.
 * Web clients use the cookie, mobile clients use the header, same backend.
 * The cookie travels encrypted (EncryptCookies on the `api` group) and
 * arrives here already decrypted; tampered values decrypt to null.
 */
class AuthenticateViaCookieOrBearer
{
    public const COOKIE = 'fixora_token';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null || $request->bearerToken() === '') {
            $cookieToken = $request->cookie(self::COOKIE);

            if (is_string($cookieToken) && $cookieToken !== '') {
                $request->headers->set('Authorization', 'Bearer '.$cookieToken);
            }
        }

        return $next($request);
    }
}
