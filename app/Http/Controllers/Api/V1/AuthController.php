<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaCookieOrBearer;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated()['email'])->first();

        if ($user === null || ! Hash::check($request->validated()['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        if (! $user->isActive()) {
            return ApiResponse::error('Account disabled.', 403);
        }

        $user->load('role.permissions');

        $token = $user->createToken($request->validated()['device_name'] ?? 'api')->plainTextToken;

        return ApiResponse::ok([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Logged in.')->withCookie(cookie(
            AuthenticateViaCookieOrBearer::COOKIE,
            $token,
            60 * 24 * 7,
            '/',
            null,
            app()->isProduction(),
            true,
            false,
            'Lax',
        ));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::ok(null, 'Logged out.')
            ->withoutCookie(AuthenticateViaCookieOrBearer::COOKIE, '/');
    }

    public function me(Request $request): JsonResponse
    {
        $request->user()->load('role.permissions');

        return ApiResponse::ok([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error('Unable to send reset link.', 422);
        }

        return ApiResponse::ok(null, 'Reset link sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error('Unable to reset password.', 422);
        }

        return ApiResponse::ok(null, 'Password reset.');
    }
}
