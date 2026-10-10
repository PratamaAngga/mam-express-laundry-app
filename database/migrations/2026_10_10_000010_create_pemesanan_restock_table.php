<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemesanan_restock', function (Blueprint $table) {
            $table->id('id_restock');
            $table->string('kode_restock', 20)->unique();
            $table->foreignId('id_supplier')
                  ->constrained('supplier', 'id_supplier')->restrictOnDelete();
            $table->foreignId('id_user_owner')
                  ->constrained('users', 'id_user')->restrictOnDelete();
            $table->enum('status', ['diajukan', 'dikonfirmasi', 'dikirim', 'diterima'])
                  ->default('diajukan');
            $table->dateTime('tanggal_pengajuan');
            $table->dateTime('tanggal_diterima')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemesanan_restock');
    }
};
