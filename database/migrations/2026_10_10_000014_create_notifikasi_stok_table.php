<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_stok', function (Blueprint $table) {
            $table->id('id_notif_stok');
            $table->foreignId('id_bahan')
                  ->constrained('bahan_baku', 'id_bahan')->cascadeOnDelete();
            $table->foreignId('id_user_owner')
                  ->constrained('users', 'id_user')->cascadeOnDelete();
            $table->text('pesan');
            $table->boolean('sudah_dibaca')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_stok');
    }
};
