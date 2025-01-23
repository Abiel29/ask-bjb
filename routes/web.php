<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\AIController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Route untuk login dan home
Route::get('/', [LoginController::class, 'index']);
Route::post('/', [LoginController::class, 'login'])->name('login');

// Route untuk logout dan AI generator hanya bisa diakses pengguna yang sudah login
Route::middleware('auth')->group(function () {
    // Halaman utama setelah login
    Route::get('/ask-bjb', function () {
        return view('index');
    })->name('ask-bjb');

    Route::get('/ask-bjb-2', function () {
        return view('index-2');
    })->name('ask-bjb-2');

    // Logout route
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // AI Generator API
    // Profiling company
    Route::post('/profile-company', [AIController::class, 'profileCompany'])->name('profile.company');

    // Chat AI biasa
    Route::post('/chat-ai', [AIController::class, 'chat'])->name('chat.ai');
});
