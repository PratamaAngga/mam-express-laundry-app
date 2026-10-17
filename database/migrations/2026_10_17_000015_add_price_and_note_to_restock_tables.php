<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemesanan_restock', function (Blueprint $table) {
            $table->text('catatan')->nullable();
            $table->dateTime('tanggal_dikonfirmasi')->nullable();
            $table->dateTime('tanggal_dikirim')->nullable();
        });

        Schema::table('detail_restock', function (Blueprint $table) {
            $table->decimal('harga_satuan', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('pemesanan_restock', function (Blueprint $table) {
            $table->dropColumn(['catatan', 'tanggal_dikonfirmasi', 'tanggal_dikirim']);
        });

        Schema::table('detail_restock', function (Blueprint $table) {
            $table->dropColumn('harga_satuan');
        });
    }
};