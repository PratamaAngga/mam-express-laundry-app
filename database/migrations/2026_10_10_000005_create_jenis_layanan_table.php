<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_layanan', function (Blueprint $table) {
            $table->id('id_layanan');
            $table->string('nama_layanan', 50);
            $table->decimal('harga_per_kg', 10, 2);
            $table->unsignedSmallInteger('estimasi_hari');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_layanan');
    }
};
