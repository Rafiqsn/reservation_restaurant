<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('jam_operasional', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restoran_id');
            $table->time('jam_buka');
            $table->time('jam_tutup');
            $table->timestamps();

            $table->foreign('restoran_id')->references('id')->on('restoran')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jam_operasional');
    }
};
