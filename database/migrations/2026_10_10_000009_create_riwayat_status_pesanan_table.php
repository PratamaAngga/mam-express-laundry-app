<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_pesanan', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->foreignId('id_pesanan')
                  ->constrained('pesanan', 'id_pesanan')->cascadeOnDelete();
            $table->foreignId('id_user')
                  ->constrained('users', 'id_user')->restrictOnDelete();
            $table->string('status_lama', 30)->nullable();
            $table->string('status_baru', 30);
            $table->dateTime('waktu_perubahan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_pesanan');
    }
};
