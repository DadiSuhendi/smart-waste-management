<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.store');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register.store');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::resource('reports', ReportController::class);
    Route::get('/reports/{report}/photo', [ReportController::class, 'photo'])->name('reports.photo');
    Route::patch('/reports/{report}/status', [ReportController::class, 'status'])->middleware('admin')->name('reports.status');
    Route::post('/predictions', PredictionController::class)->middleware('throttle:predictions')->name('predictions');
    Route::resource('users', UserController::class)->except('show')->middleware('admin');
    Route::view('/guide', 'guide')->name('guide');
});
