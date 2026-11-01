<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemesanan_restock', function (Blueprint $table) {
            $table->string('ekspedisi', 100)->nullable();
            $table->string('nama_kurir', 100)->nullable();
            $table->string('kontak_kurir', 20)->nullable();
            $table->string('nomor_resi', 50)->nullable();
            $table->dateTime('estimasi_tiba')->nullable();
            $table->text('catatan_pengiriman')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pemesanan_restock', function (Blueprint $table) {
            $table->dropColumn(['ekspedisi', 'nama_kurir', 'kontak_kurir', 'nomor_resi', 'estimasi_tiba', 'catatan_pengiriman']);
        });
    }
};