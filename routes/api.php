<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RestaurantController;

// Rute publik (tanpa autentikasi)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
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




    //Admin Field
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // users
       Route::get('/users', [AdminController::class, 'index']);
        Route::post('/users', [AdminController::class, 'store']);
        Route::get('/users/{id}', [AdminController::class, 'show']);
        Route::put('/users/{id}', [AdminController::class, 'update']);
        Route::delete('/users/{id}', [AdminController::class, 'destroy']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

     });






    //Penyedia Restoran field

    Route::middleware(['role:penyedia'])->prefix('penyedia')->group(function () {
        Route::apiResource('restoran', RestaurantController::class)->only([
            'show', 'update',
        ]);
    });







});

