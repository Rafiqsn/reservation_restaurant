<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('reservasi_menu', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservasi_id');
            $table->uuid('menu_id');
            $table->integer('jumlah');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
            $table->foreign('reservasi_id')->references('id')->on('reservasi')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menu')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('reservasi_menu');
    }
};

