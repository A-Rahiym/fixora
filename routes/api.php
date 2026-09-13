<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return ApiResponse::ok(['status' => 'ok']);
});

// Versioned API surface (Phase 1+ builds here: auth, staff, roles...).
Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return ApiResponse::ok(['status' => 'ok', 'version' => 'v1']);
    });
});
