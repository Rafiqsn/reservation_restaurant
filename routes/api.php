<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\KursiController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\MenuController;
// Rute publik (tanpa autentikasi)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');


Route::get('/landing', [LandingPageController::class, 'index']);
Route::get('/landing/resto', [LandingPageController::class, 'index']);
// Rute yang butuh token login


//Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Contoh route role-based
    Route::get('/penyedia', function (Request $request) {
        return response()->json([
            'message' => 'Halaman untuk penyedia',
            'status' => 'success',
            'user' => $request->user()
        ]);
    })->middleware(['role:penyedia'])->name('penyedia.page');

    Route::get('/admin', function () {
        return response()->json([
            'message' => 'Halaman untuk Admin',
            'status' => 'success'
        ]);
    })->middleware(['role:admin'])->name('admin.page');




    //Admin Field
   // Route::middleware(['role:admin'])->prefix('admin')->group(function () {
   Route::prefix('admin')->group(function () {
        //dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
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

        //web
        Route::put('/restoran/{id}/rekomendasi', [AdminController::class, 'toggleRecommendation']);
        Route::get('/restoran', [AdminController::class, 'adminIndex']);

        //edit profile
        Route::get('/edit/{id}', [AdminController::class, 'adminProfileShow']);
        Route::put('/edit/{id}', [AdminController::class, 'adminProfileUpdate']);

     });






    //Penyedia Restoran field
    //Route::middleware(['role:penyedia'])->prefix('penyedia')->group(function () {
        //dashboard
        Route::prefix('penyedia')->group(function () {
        Route::get('/dashboard', [RestaurantController::class, 'dashboard']);
        //manajemen meja

        Route::prefix('kursi')->group(function () {
            Route::get('', [KursiController::class, 'index']);
            Route::post('', [KursiController::class, 'store']);
            Route::post('/upload-denah/{id}', [KursiController::class, 'uploadDenah']);
            Route::delete('/{id}', [KursiController::class, 'destroy']);
        });

        //kelola reservasi
        Route::get('/reservasi', [ReservasiController::class, 'index']);
        Route::put('/reservasi/{id}/status', [ReservasiController::class, 'updateStatus']);

        //kelola menu
        Route::get('/menu', [MenuController::class, 'index']);
        Route::post('/menu', [MenuController::class, 'store']);
        Route::put('/menu/{id}', [MenuController::class, 'update']);
        Route::delete('/menu/{id}', [MenuController::class, 'destroy']);

        //lihat ulasan
        Route::get('/ulasan', [RestaurantController::class, 'ulasan']);
        //pengaturan
        Route::get('/restoran', [RestaurantController::class, 'showuser']);
        Route::put('/restoran', [RestaurantController::class, 'updateuser']);
    });



   //pemesan Field
   //Route::middleware(['role:pemesan'])->prefix('pemesan')->group(function () {
    Route::prefix('pemesan')->group(function () {
        Route::get('/restoran', [CustomerController::class, 'search']);
        Route::get('/restoran', [CustomerController::class, 'index']);
        Route::get('/restoran/{id}', [CustomerController::class, 'show']);

        Route::post('/reservasi/cek-ketersediaan', [ReservasiController::class, 'cekKetersediaan']);

        Route::get('/reservasi/menu', [ReservasiController::class, 'getMenu']);
        Route::post('/reservasi/menu', [ReservasiController::class, 'tampilkanMenu']);

    });



//});

