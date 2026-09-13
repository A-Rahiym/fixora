<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level permission gate for the API.
 *
 * Usage: `->middleware('permission:staff.view')`. Checks the
 * authenticated user's role permission set via HasPermissions —
 * the single gate check reused by every phase from here on.
 */
class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission($permission)) {
            return response()->json([
                'message' => 'Forbidden.',
                'errors' => [],
            ], 403);
        }

        return $next($request);
    }
}
