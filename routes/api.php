<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('/me', fn (Request $request) => response()->json(['data' => $request->user()]));
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('reports', ReportController::class);
        Route::get('/reports/{report}/photo', [ReportController::class, 'photo'])->name('reports.photo');
        Route::patch('/reports/{report}/status', [ReportController::class, 'status'])->middleware('admin');
        Route::post('/predictions', PredictionController::class)->middleware('throttle:predictions');
        Route::apiResource('users', UserController::class)->except('show')->middleware('admin');
    });
});
