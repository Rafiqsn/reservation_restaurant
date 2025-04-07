<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rute publik (tanpa autentikasi)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rute yang butuh token login
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin-area', function () {
    return response()->json(['msg' => 'Halo admin!']);
});
/*
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Contoh route role-based
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin-area', fn() => response()->json(['msg' => 'Welcome admin']));
    });
});
*/
