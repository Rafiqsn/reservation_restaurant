<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('reservasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pengguna_id');
            $table->uuid('restoran_id');
            $table->uuid('kursi_id')->nullable(); // Ubah ini
            $table->date('tanggal');
            $table->time('waktu');
            $table->enum('status', ['menunggu', 'dikonfirmasi', 'dibatalkan']);
            $table->integer('jumlah_orang');
            $table->text('catatan')->nullable();
            $table->string('nomor_reservasi')->nullable();
            $table->decimal('total_harga', 10, 2)->nullable();
            $table->timestamps();
            $table->foreign('pengguna_id')->references('id')->on('pengguna')->onDelete('cascade');
            $table->foreign('restoran_id')->references('id')->on('restoran')->onDelete('cascade');
            $table->foreign('kursi_id')->references('id')->on('kursi')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('reservasi');
    }
};
