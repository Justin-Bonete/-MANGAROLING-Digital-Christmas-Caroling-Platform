<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Authenticated, active accounts
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);

        // Everything below requires the temporary password to have been changed.
        Route::middleware('password.changed')->group(function () {
            Route::middleware('can:users.manage')->group(function () {
                Route::get('roles', [RoleController::class, 'index']);
                Route::get('users', [UserController::class, 'index']);
                Route::post('users', [UserController::class, 'store']);
                Route::get('users/{user}', [UserController::class, 'show']);
                Route::put('users/{user}', [UserController::class, 'update']);
                Route::patch('users/{user}/active', [UserController::class, 'setActive']);
                Route::put('users/{user}/roles', [UserController::class, 'syncRoles']);
                Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
            });

            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:audit_logs.view');
        });
    });
});
