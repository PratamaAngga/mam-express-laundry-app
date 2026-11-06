<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_restock', function (Blueprint $table) {
            // Jumlah yang benar-benar datang (bisa kurang dari yang dipesan). Null = belum dikonfirmasi diterima
            $table->decimal('jumlah_diterima', 12, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detail_restock', function (Blueprint $table) {
            $table->dropColumn('jumlah_diterima');
        });
    }
};
