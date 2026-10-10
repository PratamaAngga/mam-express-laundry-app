<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_wa', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->enum('tipe', ['invoice_pelanggan', 'invoice_supplier']);
            $table->foreignId('id_pesanan')->nullable()
                  ->constrained('pesanan', 'id_pesanan')->nullOnDelete();
            $table->foreignId('id_restock')->nullable()
                  ->constrained('pemesanan_restock', 'id_restock')->nullOnDelete();
            $table->string('no_tujuan', 20);
            $table->text('isi_pesan');
            $table->enum('status_kirim', ['pending', 'terkirim', 'gagal'])->default('pending');
            $table->dateTime('waktu_kirim')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_wa');
    }
};
