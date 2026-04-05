<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication endpoints
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
});

// Protected API endpoints
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Authentication endpoints
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('user', [AuthController::class, 'user']);
        Route::get('tokens', [AuthController::class, 'tokens']);
        Route::delete('tokens/{token}', [AuthController::class, 'revokeToken']);
    });

    // HRMS API endpoints
    Route::apiResource('employees', EmployeeController::class)->middleware('throttle:admin');
    Route::get('payroll', [PayrollController::class, 'index'])->middleware('throttle:sensitive');
    Route::post('attendance', [AttendanceController::class, 'store'])->middleware('throttle:api');
    Route::get('reports', [ReportController::class, 'index'])->middleware('throttle:sensitive');
});
