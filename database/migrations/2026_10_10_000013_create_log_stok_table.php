<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_stok', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('id_bahan')
                  ->constrained('bahan_baku', 'id_bahan')->restrictOnDelete();
            $table->foreignId('id_restock')->nullable()
                  ->constrained('pemesanan_restock', 'id_restock')->nullOnDelete();
            $table->enum('tipe', ['masuk', 'keluar', 'penyesuaian']);
            $table->decimal('jumlah', 10, 2);
            $table->dateTime('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_stok');
    }
};
