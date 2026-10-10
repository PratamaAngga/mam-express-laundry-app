<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->id('id_supplier');
            $table->foreignId('id_user')->unique()
                  ->constrained('users', 'id_user')->restrictOnDelete();
            $table->string('nama_supplier', 100);
            $table->string('no_whatsapp', 20);
            $table->text('alamat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier');
    }
};
