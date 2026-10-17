<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_layanan', function (Blueprint $table) {
            $table->boolean('status_aktif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('jenis_layanan', function (Blueprint $table) {
            $table->dropColumn('status_aktif');
        });
    }
};
