<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rute publik (tanpa autentikasi)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rute yang butuh token login


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Contoh route role-based
    Route::get('/penyedia-page', function () {
        return response()->json([
            'message' => 'Halaman untuk penyedia',
            'status' => 'success'
        ]);
    })->middleware(['role:penyedia'])->name('penyedia.page');

    Route::get('/admin-page', function () {
        return response()->json([
            'message' => 'Halaman untuk Admin',
            'status' => 'success'
        ]);
    })->middleware(['role:admin'])->name('admin.page');

});

