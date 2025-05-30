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
use App\Http\Controllers\UlasanController;

// Rute publik (tanpa autentikasi)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');


Route::get('/landing', [LandingPageController::class, 'index']);
Route::get('/restoran/{id}', [LandingPageController::class, 'show']);
Route::get('/restoran', [LandingPageController::class, 'search']);

Route::get('/rekomendasi', [LandingPageController::class, 'rekomendasi']);
Route::get('/terdekat', [LandingPageController::class, 'terdekat']);



// Rute yang butuh token login
Route::middleware('auth:sanctum')->group(function () {
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

    Route::get('/pemesan', function () {
        return response()->json([
            'message' => 'Halaman untuk pemesan',
            'status' => 'success'
        ]);
    })->middleware(['role:pemesan'])->name('pemesan.page');




    //Admin Field
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
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
        Route::put('/restoran/rekomendasi/{id}', [AdminController::class, 'toggleRecommendation']);
        Route::get('/restoran', [AdminController::class, 'adminIndex']);

        //edit profile
        Route::get('/profile/edit/{id}', [AdminController::class, 'adminProfileShow']);
        Route::put('/profile/edit/{id}', [AdminController::class, 'adminProfileUpdate']);

     });

    //Penyedia Restoran field
    Route::middleware(['role:penyedia'])->prefix('penyedia')->group(function () {
        //dashboard
        Route::get('/dashboard', [RestaurantController::class, 'dashboard']);
        Route::put('/dashboard/{id}', [RestaurantController::class, 'updateOperasional']);

        //manajemen meja
        Route::get('/kursi', [KursiController::class, 'index']);
        Route::post('/kursi', [KursiController::class, 'store']);
        Route::post('/kursi/upload-denah/{id}', [KursiController::class, 'uploadDenah']);
        Route::delete('/kursi/{id}', [KursiController::class, 'destroy']);

        //kelola reservasi
        Route::get('/reservasi', [ReservasiController::class, 'daftarReservasi']);
        Route::get('/reservasi/konfirmasi/{reservasi_id}', [ReservasiController::class, 'GetNotaRiwayat']);
        Route::put('/reservasi/konfirmasi/{reservasi_id}', [ReservasiController::class, 'konfirmasi']);
        Route::put('/reservasi/batalkan/{reservasi_id}', [ReservasiController::class, 'batalkan']);

        //kelola menu
        Route::get('/menu', [MenuController::class, 'index']);
        Route::post('/menu', [MenuController::class, 'store']);
        Route::put('/menu/{id}', [MenuController::class, 'update']);
        Route::delete('/menu/{id}', [MenuController::class, 'destroy']);

        //lihat ulasan
        // routes/api.php
        Route::get('/restoran/ulasan/{id}', [UlasanController::class, 'lihatUlasanRestoran']);

        //pengaturan
        Route::get('/restoran', [RestaurantController::class, 'showuser']);
        Route::put('/restoran', [RestaurantController::class, 'updateuser']);
    });


    Route::middleware(['role:pemesan'])->prefix('pemesan')->group(function () {
    //Alur Pesan
        //isi format
        Route::post('/reservasi/cek-ketersediaan', [ReservasiController::class, 'cekKetersediaan']);
        //Pilih Menu
        Route::get('/reservasi/pilih-menu', [ReservasiController::class, 'GetMenu']);
        Route::post('/reservasi/pilih-menu', [ReservasiController::class, 'PilihMenu']);
        //Pilih Kursi
        Route::get('/reservasi/pilih-kursi', [ReservasiController::class, 'GetKursi']);
        Route::post('/reservasi/pilih-kursi', [ReservasiController::class, 'PilihKursi']);
        //Get nota
        Route::get('/reservasi/konfirmasi-pesanan/{id}', [ReservasiController::class, 'GetNota']);
        Route::post('/reservasi/konfirmasi-pesanan', [ReservasiController::class, 'tambahCatatan']);

    //Profil Setting
        //Profil Page
        Route::get('/profil', [CustomerController::class, 'CustShow']);
        Route::put('/profil/{id}', [CustomerController::class, 'updateProfile']);
        //Pesenan saya
        Route::get('/reservasi/pesanan-saya', [ReservasiController::class, 'PesananSaya']);
        //Riwayat Pesanan
        Route::get('/reservasi/riwayatpesanan', [ReservasiController::class, 'riwayatpesanan']);
            // Lihat nota Pesanan
         Route::get('/reservasi/lihat-nota/{reservasi_id}', [ReservasiController::class, 'GetNotaRiwayat']);
            //berikan penilaian
        Route::post('/reservasi/lihat-nota/ulasan', [UlasanController::class, 'store']);



    });
});

