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
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->string('kontak');
            $table->longText('foto')->nullable();
            $table->string('nib')->nullable();
            $table->string('surat_halal')->nullable();

            $table->boolean('is_recommended')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->foreign('pemilik_id')->references('id')->on('pengguna')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('restoran');
    }
};

