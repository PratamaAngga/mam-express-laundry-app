<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_restock', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_restock')
                  ->constrained('pemesanan_restock', 'id_restock')->cascadeOnDelete();
            $table->foreignId('id_bahan')
                  ->constrained('bahan_baku', 'id_bahan')->restrictOnDelete();
            $table->decimal('jumlah', 10, 2);
            $table->enum('status', ['diajukan', 'dikonfirmasi', 'dikirim', 'diterima'])
                  ->default('diajukan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_restock');
    }
};
