
<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthProfileController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestCaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

// Login, Register, dan Forgot Password
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/auth/google', [
    GoogleAuthController::class,
    'redirect'
    ])->name('google.redirect');

    Route::get('/auth/google/callback', [
    GoogleAuthController::class,
    'callback'
    ])->name('google.callback');

    // F4 - Forgot Password
    Route::view('/forgot-password', 'auth.reset-password')->name('password.request');

    Route::post('/forgot-password', [
        ForgotPasswordController::class,
        'sendResetLink'
    ])->name('password.email');

    Route::get('/reset-password/{token}', [
        ResetPasswordController::class,
        'showResetForm'
    ])->name('password.reset');

    Route::post('/reset-password', [
        ResetPasswordController::class,
        'reset'
    ])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Console, Collection, Test Case, dan Auth Profile
Route::middleware('auth')->group(function () {

    // F14 - Collection & Console
    Route::get('/console', ConsoleController::class)->name('console');

    Route::resource('collections', CollectionController::class)
        ->only(['store', 'update', 'destroy']);

    // F6 & F8 - Test Case dan Scenario URL
    Route::post('/test-cases', [
        TestCaseController::class,
        'store'
    ])->name('test-cases.store');

    Route::get('/test-cases/{test_case}/scenario-urls', [
        TestCaseController::class,
        'scenarioUrls'
    ])->name('test-cases.scenario-urls');

    Route::post('/test-cases/{test_case}/scenario-urls', [
        TestCaseController::class,
        'generate'
    ])->name('test-cases.scenario-urls.generate');

    Route::get('/test-cases/{test_case}', [
        TestCaseController::class,
        'show'
    ])->name('test-cases.show');

    // F7 - Auth Profile Config
    Route::put('/test-cases/{testCaseId}/auth-profile', [
        AuthProfileController::class,
        'upsert'
    ])->whereNumber('testCaseId')->name('auth-profiles.upsert');

    Route::delete('/test-cases/{testCaseId}/auth-profile', [
        AuthProfileController::class,
        'destroy'
    ])->whereNumber('testCaseId')->name('auth-profiles.destroy');

    // F17 & F18 - Profile Settings
    Route::get('/profile', [
        ProfileController::class,
        'show'
    ])->name('profile');

    Route::patch('/profile', [
        ProfileController::class,
        'update'
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy'
    ])->name('profile.destroy');
});