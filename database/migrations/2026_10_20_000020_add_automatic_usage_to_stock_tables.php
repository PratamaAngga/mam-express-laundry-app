<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ketelitian stok dinaikkan ke 3 desimal: 10 ml/kg x 4,5 kg = 0,045 liter, tidak muat di 2 desimal
        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->decimal('stok_saat_ini', 12, 3)->default(0)->change();
            $table->decimal('stok_minimum', 12, 3)->default(0)->change();
        });

        Schema::table('log_stok', function (Blueprint $table) {
            $table->decimal('jumlah', 12, 3)->change();
        });

        Schema::table('bahan_baku', function (Blueprint $table) {
            // Pemakaian per kg cucian, dalam satuan bahan itu sendiri (liter 0,010 = 10 ml). Null = tidak dipotong otomatis
            $table->decimal('pemakaian_per_kg', 10, 4)->nullable();
            // Kalau diisi, bahan hanya terpakai pada pesanan yang memilih layanan tambahan ini (mis. pewangi)
            $table->foreignId('id_tambahan_pemicu')->nullable()
                ->constrained('layanan_tambahan', 'id_tambahan')->nullOnDelete();
        });

        Schema::table('log_stok', function (Blueprint $table) {
            // Pesanan penyebab pemakaian; unique mencegah satu pesanan memotong bahan yang sama dua kali
            $table->foreignId('id_pesanan')->nullable()
                ->constrained('pesanan', 'id_pesanan')->nullOnDelete();
            $table->unique(['id_bahan', 'id_pesanan', 'tipe'], 'log_stok_pemakaian_pesanan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('log_stok', function (Blueprint $table) {
            $table->dropUnique('log_stok_pemakaian_pesanan_unique');
            $table->dropConstrainedForeignId('id_pesanan');
        });

        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_tambahan_pemicu');
            $table->dropColumn('pemakaian_per_kg');
        });

        Schema::table('log_stok', function (Blueprint $table) {
            $table->decimal('jumlah', 10, 2)->change();
        });

        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->decimal('stok_saat_ini', 10, 2)->default(0)->change();
            $table->decimal('stok_minimum', 10, 2)->default(0)->change();
        });
    }
};
