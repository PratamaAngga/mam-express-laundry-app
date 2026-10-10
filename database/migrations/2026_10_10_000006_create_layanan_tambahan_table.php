<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layanan_tambahan', function (Blueprint $table) {
            $table->id('id_tambahan');
            $table->string('nama_tambahan', 50);
            $table->decimal('harga', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan_tambahan');
    }
};
