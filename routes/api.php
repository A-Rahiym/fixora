<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return ApiResponse::ok(['status' => 'ok']);
});

// Versioned API surface (Phase 1: identity & access).
Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return ApiResponse::ok(['status' => 'ok', 'version' => 'v1']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');
    });

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        Route::get('/staff', [StaffController::class, 'index'])->middleware('permission:staff.view');
        Route::post('/staff', [StaffController::class, 'store'])->middleware('permission:staff.create');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->middleware('permission:staff.view');
        Route::patch('/staff/{staff}', [StaffController::class, 'update'])->middleware('permission:staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->middleware('permission:staff.disable');

        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.update');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update');
        Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:roles.view');
    });
});
