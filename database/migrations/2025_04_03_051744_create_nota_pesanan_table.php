<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('nota_pesanan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservasi_id');
            $table->decimal('total_harga', 10, 2);
            $table->timestamps();

            $table->foreign('reservasi_id')->references('id')->on('reservasi')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('nota_pesanan');
    }
};
