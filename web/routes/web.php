<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Lupa password
    Route::view('/forgot-password', 'auth.reset-password')->name('password.request');
    Route::post('/forgot-password', fn () => back()->with('status', 'Link reset sudah dikirim (placeholder).'))->name('password.email');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// F14 - Collection & Console
Route::middleware('auth')->group(function () {
    Route::get('/console', ConsoleController::class)->name('console');
    Route::resource('collections', CollectionController::class)->only(['store', 'update', 'destroy']);
    Route::get('/test-cases/{testCase}/script/download', [ConsoleController::class, 'download'])->name('test-cases.script.download');
});

// F17 & F18 - Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
