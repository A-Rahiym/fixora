<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Global API response envelope (brief §62).
 *
 * Every Phase 1+ controller returns through here so success/error
 * payloads stay consistent: `{data, message}` on success,
 * `{message, errors}` on failure.
 */
class ApiResponse
{
    public static function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return self::ok($data, $message, 201);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
