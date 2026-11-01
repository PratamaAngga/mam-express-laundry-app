<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Aturan CHECK lama hanya mengizinkan 2 tipe dan 3 status; dihapus lalu dipasang ulang dengan nilai baru
            foreach (DB::select("select conname from pg_constraint where conrelid = 'notifikasi_wa'::regclass and contype = 'c'") as $constraint) {
                DB::statement('ALTER TABLE notifikasi_wa DROP CONSTRAINT "' . $constraint->conname . '"');
            }

            DB::statement("ALTER TABLE notifikasi_wa ADD CONSTRAINT notifikasi_wa_tipe_check CHECK (tipe IN ('invoice_pelanggan', 'invoice_supplier', 'status_pelanggan', 'peringatan_stok'))");
            // simulasi = dicatat oleh driver log, TIDAK benar-benar dikirim
            DB::statement("ALTER TABLE notifikasi_wa ADD CONSTRAINT notifikasi_wa_status_kirim_check CHECK (status_kirim IN ('pending', 'terkirim', 'gagal', 'simulasi'))");
        }

        Schema::table('notifikasi_wa', function (Blueprint $table) {
            $table->unsignedSmallInteger('percobaan')->default(0);
            $table->text('alasan_gagal')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_wa', function (Blueprint $table) {
            $table->dropColumn(['percobaan', 'alasan_gagal']);
        });

        if (DB::getDriverName() === 'pgsql') {
            // Baris dengan nilai baru dibersihkan supaya aturan lama bisa dipasang kembali
            DB::statement("DELETE FROM notifikasi_wa WHERE tipe NOT IN ('invoice_pelanggan', 'invoice_supplier')");
            DB::statement("UPDATE notifikasi_wa SET status_kirim = 'gagal' WHERE status_kirim = 'simulasi'");
            DB::statement('ALTER TABLE notifikasi_wa DROP CONSTRAINT IF EXISTS notifikasi_wa_tipe_check');
            DB::statement('ALTER TABLE notifikasi_wa DROP CONSTRAINT IF EXISTS notifikasi_wa_status_kirim_check');
            DB::statement("ALTER TABLE notifikasi_wa ADD CONSTRAINT notifikasi_wa_tipe_check CHECK (tipe IN ('invoice_pelanggan', 'invoice_supplier'))");
            DB::statement("ALTER TABLE notifikasi_wa ADD CONSTRAINT notifikasi_wa_status_kirim_check CHECK (status_kirim IN ('pending', 'terkirim', 'gagal'))");
        }
    }
};
