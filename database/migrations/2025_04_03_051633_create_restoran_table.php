<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('restoran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pemilik_id');
            $table->string('nama');
            $table->text('lokasi');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['buka', 'tutup']);
            $table->time('jam_operasional');
            $table->string('kontak');
            $table->longText('foto')->nullable();
            $table->longText('foto_sorotan_menu')->nullable();
            $table->text('sorotan_menu')->nullable();
            $table->timestamps();

            $table->foreign('pemilik_id')->references('id')->on('pengguna')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('restoran');
    }
};

