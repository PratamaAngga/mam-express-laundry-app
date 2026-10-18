<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemesanan_restock', function (Blueprint $table) {
            $table->dateTime('perkiraan_kirim')->nullable();
            $table->text('catatan_konfirmasi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pemesanan_restock', function (Blueprint $table) {
            $table->dropColumn(['perkiraan_kirim', 'catatan_konfirmasi']);
        });
    }
};
