<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan_tambahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')
                  ->constrained('pesanan', 'id_pesanan')->cascadeOnDelete();
            $table->foreignId('id_tambahan')
                  ->constrained('layanan_tambahan', 'id_tambahan')->restrictOnDelete();
            $table->decimal('harga_saat_ini', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_tambahan');
    }
};
