<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('menu', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('restoran_id');
        $table->string('nama');
        $table->text('deskripsi')->nullable();
        $table->enum('jenis', ['makanan', 'minuman']);
        $table->decimal('harga', 10, 2);
        $table->longText('foto')->nullable();
        $table->enum('status', ['tersedia', 'tidak_tersedia'])->default('tersedia');
        $table->boolean('highlight')->default(false);
        $table->timestamps();

        $table->foreign('restoran_id')->references('id')->on('restoran')->onDelete('cascade');
    });
    }

    public function down() {
        Schema::dropIfExists('menu');
    }
};

