<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\RepairController;
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

    Route::middleware(['token.cookie', 'auth:sanctum', 'active'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        Route::prefix('dashboard')->middleware('permission:dashboard.view')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary']);
            Route::get('/repair-pipeline', [DashboardController::class, 'repairPipeline']);
            Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
            Route::get('/low-stock', [DashboardController::class, 'lowStock']);
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

        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view');
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.create');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
        Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete');
        Route::get('/customers/{customer}/devices', [CustomerController::class, 'devices'])->middleware('permission:devices.view');

        Route::get('/devices', [DeviceController::class, 'index'])->middleware('permission:devices.view');
        Route::post('/devices', [DeviceController::class, 'store'])->middleware('permission:devices.create');
        Route::get('/devices/{device}', [DeviceController::class, 'show'])->middleware('permission:devices.view');
        Route::patch('/devices/{device}', [DeviceController::class, 'update'])->middleware('permission:devices.update');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->middleware('permission:devices.delete');
        Route::get('/devices/{device}/repairs', [DeviceController::class, 'repairs'])->middleware('permission:repairs.view');

        Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->middleware('permission:inventory.view');
        Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view');
        Route::post('/inventory', [InventoryController::class, 'store'])->middleware('permission:inventory.create');
        Route::get('/inventory/{item}', [InventoryController::class, 'show'])->middleware('permission:inventory.view');
        Route::patch('/inventory/{item}', [InventoryController::class, 'update'])->middleware('permission:inventory.update');
        Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.delete');
        Route::get('/inventory/{item}/movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view');
        Route::post('/inventory/{item}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust');

        Route::get('/repairs', [RepairController::class, 'index'])->middleware('permission:repairs.view');
        Route::post('/repairs', [RepairController::class, 'store'])->middleware('permission:repairs.create');
        Route::get('/repairs/{repair}', [RepairController::class, 'show'])->middleware('permission:repairs.view');
        Route::patch('/repairs/{repair}', [RepairController::class, 'update'])->middleware('permission:repairs.update');
        Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->middleware('permission:repairs.delete');
        Route::post('/repairs/{repair}/assign', [RepairController::class, 'assign'])->middleware('permission:repairs.assign');
        Route::post('/repairs/{repair}/diagnosis', [RepairController::class, 'diagnosis'])->middleware('permission:repairs.update');
        Route::post('/repairs/{repair}/notes', [RepairController::class, 'notes'])->middleware('permission:repairs.update');
        Route::post('/repairs/{repair}/approve', [RepairController::class, 'approve'])->middleware('permission:repairs.update');
        Route::post('/repairs/{repair}/status', [RepairController::class, 'status'])->middleware('permission:repairs.update');
        Route::post('/repairs/{repair}/complete', [RepairController::class, 'complete'])->middleware('permission:repairs.update');
        Route::post('/repairs/{repair}/collect', [RepairController::class, 'collect'])->middleware('permission:repairs.update');
    });
});
