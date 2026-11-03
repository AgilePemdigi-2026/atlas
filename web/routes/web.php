<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\TestCaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

// Lupa password
Route::view('/forgot-password', 'auth.reset-password')->name('password.request');
Route::post('/forgot-password', [
    ForgotPasswordController::class,
    'sendResetLink'
    ])->name('password.email');
    
// Reset password
Route::get('/reset-password/{token}', [
    ResetPasswordController::class,
    'showResetForm'
    ])->name('password.reset');

    Route::post('/reset-password', [
        ResetPasswordController::class,
    'reset'
    ])->name('password.update');
});

Route::get('/console', [
    ConsoleController::class,
    'index'
])->middleware('auth')->name('console');

Route::post('/test-cases', [
    TestCaseController::class,
    'store'
])->middleware('auth')->name('test-cases.store');

Route::middleware('auth')->group(function () {
    Route::put('/test-cases/{testCaseId}/auth-profile', [
        AuthProfileController::class,
        'upsert'
    ])->whereNumber('testCaseId')->name('auth-profiles.upsert');

    Route::delete('/test-cases/{testCaseId}/auth-profile', [
        AuthProfileController::class,
        'destroy'
    ])->whereNumber('testCaseId')->name('auth-profiles.destroy');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');