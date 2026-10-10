<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->id('id_bahan');
            $table->string('nama_bahan', 100);
            $table->string('satuan', 20);
            $table->decimal('stok_saat_ini', 10, 2)->default(0);
            $table->decimal('stok_minimum', 10, 2)->default(0);
            $table->foreignId('id_supplier')
                  ->constrained('supplier', 'id_supplier')->restrictOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};
