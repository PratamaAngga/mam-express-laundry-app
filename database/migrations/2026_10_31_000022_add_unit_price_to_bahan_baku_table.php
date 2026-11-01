<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga satuan produk sekarang diatur oleh supplier (bukan lagi diketik owner saat restock).
 * Harga disimpan di bahan_baku; detail_restock.harga_satuan tetap menjadi salinan harga saat pesanan dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->decimal('harga_satuan', 12, 2)->default(0);
        });

        // Isi awal dari harga restock terakhir tiap bahan, supaya data yang sudah ada tidak kosong
        foreach (DB::table('bahan_baku')->pluck('id_bahan') as $id) {
            $price = DB::table('detail_restock')
                ->where('id_bahan', $id)
                ->where('harga_satuan', '>', 0)
                ->orderByDesc('id_detail')
                ->value('harga_satuan');

            if ($price !== null) {
                DB::table('bahan_baku')->where('id_bahan', $id)->update(['harga_satuan' => $price]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->dropColumn('harga_satuan');
        });
    }
};