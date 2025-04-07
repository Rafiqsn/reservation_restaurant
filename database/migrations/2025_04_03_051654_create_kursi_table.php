<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('kursi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restoran_id');
            $table->integer('nomor_kursi');
            $table->integer('kapasitas');
            $table->text('posisi');
            $table->enum('status', ['tersedia', 'dipesan']);
            $table->timestamps();

            $table->foreign('restoran_id')->references('id')->on('restoran')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('kursi');
    }
};

