<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\StockLog;
use App\Models\StockNotification;
use App\Models\User;

/**
 * Pengurangan stok bahan baku otomatis (milik backend).
 * Aturan: tiap bahan punya pemakaian_per_kg; bahan dengan id_tambahan_pemicu hanya terpakai pada
 * pesanan yang memilih layanan tambahan itu. Dipotong saat pesanan mulai diproses.
 */
class StockService
{
    /** Kebutuhan bahan untuk berat tertentu, dibulatkan ke 3 desimal (sama dengan ketelitian kolom stok). */
    public static function amount(float $kg, float $ratePerKg): float
    {
        return round($kg * $ratePerKg, 3);
    }

    // Sama dengan definisi di RawMaterial::isLow: stok di bawah batas minimum
    public static function isLow(float $stock, float $minimum): bool
    {
        return $stock < $minimum;
    }

    /** Stok baru saja "menyeberang" dari aman ke di bawah batas (peringatan hanya dikirim saat ini). */
    public static function crossedIntoLow(float $before, float $after, float $minimum): bool
    {
        return ! self::isLow($before, $minimum) && self::isLow($after, $minimum);
    }

    /**
     * Potong stok untuk satu pesanan. Dipanggil dari dalam transaksi perubahan status,
     * jadi kalau gagal, status pesanan ikut batal. Aman dipanggil dua kali (tidak memotong ganda).
     */
    public function consumeForOrder(Order $order, User $actor): void
    {
        $order->loadMissing('additionalServices');
        $addonIds = $order->additionalServices->pluck('id_tambahan')->all();
        $kg = (float) $order->berat_kg;
        $now = now();

        $materials = RawMaterial::whereNotNull('pemakaian_per_kg')
            ->where(function ($q) use ($addonIds) {
                $q->whereNull('id_tambahan_pemicu');
                if ($addonIds) {
                    $q->orWhereIn('id_tambahan_pemicu', $addonIds);
                }
            })
            ->orderBy('id_bahan') // urutan tetap mencegah saling kunci antar transaksi
            ->lockForUpdate()
            ->get();

        foreach ($materials as $material) {
            $alreadyLogged = StockLog::where('id_bahan', $material->id_bahan)
                ->where('id_pesanan', $order->id_pesanan)
                ->where('tipe', 'keluar')
                ->exists();

            if ($alreadyLogged) {
                continue;
            }

            $before = (float) $material->stok_saat_ini;
            // Stok tidak boleh negatif dan produksi tidak boleh berhenti hanya karena data stok meleset
            $used = min(self::amount($kg, (float) $material->pemakaian_per_kg), $before);

            if ($used <= 0) {
                continue;
            }

            $after = round($before - $used, 3);

            $material->stok_saat_ini = $after;
            $material->save();

            StockLog::create([
                'id_bahan'   => $material->id_bahan,
                'id_restock' => null,
                'id_pesanan' => $order->id_pesanan,
                'tipe'       => 'keluar',
                'jumlah'     => $used,
                'waktu'      => $now,
            ]);

            if (self::crossedIntoLow($before, $after, (float) $material->stok_minimum)) {
                $this->notifyLowStock($material, $after);
            }
        }
    }

    private function notifyLowStock(RawMaterial $material, float $remaining): void
    {
        $owner = User::where('role', 'owner_admin')->where('status_aktif', true)->orderBy('id_user')->first();

        if (! $owner) {
            return;
        }

        $unit = $material->satuan;

        StockNotification::create([
            'id_bahan'      => $material->id_bahan,
            'id_user_owner' => $owner->id_user,
            'pesan'         => "Stok {$material->nama_bahan} menipis: sisa " . RawMaterial::formatNumber($remaining)
                . " {$unit} (batas minimum " . RawMaterial::formatNumber($material->stok_minimum) . " {$unit}). Segera ajukan restock.",
        ]);

        // TODO (Modul WhatsApp): kirim juga peringatan ini ke WA owner lewat antrian
    }
}
