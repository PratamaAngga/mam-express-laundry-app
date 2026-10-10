<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->string('kode_pesanan', 20)->unique();
            $table->foreignId('id_pelanggan')
                  ->constrained('pelanggan', 'id_pelanggan')->restrictOnDelete();
            $table->foreignId('id_user_kasir')
                  ->constrained('users', 'id_user')->restrictOnDelete();
            $table->foreignId('id_layanan')
                  ->constrained('jenis_layanan', 'id_layanan')->restrictOnDelete();
            $table->decimal('berat_kg', 5, 2);
            $table->decimal('total_harga', 12, 2);
            $table->enum('status', ['antre_cuci', 'diproses', 'dikemas_siap_diambil', 'selesai'])
                  ->default('antre_cuci');
            $table->string('token_tracking', 64)->unique();
            $table->dateTime('waktu_pesanan');
            $table->dateTime('waktu_selesai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
