<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ulasan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservasi_id')->unique(); // 1 reservasi hanya 1 ulasan
            $table->uuid('restoran_id');
            $table->uuid('pengguna_id');
            $table->tinyInteger('rating'); // 1-5
            $table->text('komentar')->nullable();
            $table->timestamps();

            $table->foreign('reservasi_id')->references('id')->on('reservasi')->onDelete('cascade');
            $table->foreign('restoran_id')->references('id')->on('restoran')->onDelete('cascade');
            $table->foreign('pengguna_id')->references('id')->on('pengguna')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ulasan');
    }
};
