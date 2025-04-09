<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;

// Rute publik (tanpa autentikasi)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rute yang butuh token login


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Contoh route role-based
    Route::get('/penyedia', function () {
        return response()->json([
            'message' => 'Halaman untuk penyedia',
            'status' => 'success'
        ]);
    })->middleware(['role:penyedia'])->name('penyedia.page');

    Route::get('/admin', function () {
        return response()->json([
            'message' => 'Halaman untuk Admin',
            'status' => 'success'
        ]);
    })->middleware(['role:admin'])->name('admin.page');

    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::apiResource('users', AdminController::class)->only([
            'index', 'store', 'show', 'update', 'destroy'
        ]);
        Route::apiResource('customers', CustomerController::class)->only([
            'index', 'show', 'update','destroy'
        ]);
    });

});

