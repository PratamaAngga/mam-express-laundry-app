<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransition;
use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\RestockOrder;
use App\Models\StockLog;
use App\Models\StockNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

    // Sama dengan definisi di RawMaterial::isLow: stok yang menyentuh atau di bawah batas minimum (FR-12)
    public static function isLow(float $stock, float $minimum): bool
    {
        return $stock <= $minimum;
    }

    /** Stok baru saja "menyeberang" dari aman ke di bawah batas (peringatan hanya dikirim saat ini). */
    public static function crossedIntoLow(float $before, float $after, float $minimum): bool
    {
        return ! self::isLow($before, $minimum) && self::isLow($after, $minimum);
    }

    /**
     * Satuan input laju pemakaian di halaman owner: bahan bersatuan liter diketik dalam ml per kg
     * (lebih mudah dipahami), selebihnya memakai satuan bahan itu sendiri.
     *
     * @return array{label: string, factor: float} factor = pengali dari satuan input ke satuan bahan
     */
    public static function usageUnit(string $satuan): array
    {
        return match (strtolower(trim($satuan))) {
            'l', 'liter', 'liters', 'litre', 'litres' => ['label' => 'ml', 'factor' => 0.001],
            default => ['label' => $satuan, 'factor' => 1.0],
        };
    }

    // Nilai yang diketik owner (mis. 10 ml/kg) -> disimpan dalam satuan bahan (0,010 liter/kg)
    public static function toStoredRate(float $input, string $satuan): float
    {
        return round($input * self::usageUnit($satuan)['factor'], 4);
    }

    // Kebalikannya, untuk ditampilkan lagi di formulir
    public static function toDisplayRate(float $stored, string $satuan): float
    {
        return round($stored / self::usageUnit($satuan)['factor'], 3);
    }

    /** Alasan jumlah yang diterima tidak valid; null kalau boleh (0 sampai jumlah yang dipesan). */
    public static function receiptError(float $ordered, float $received): ?string
    {
        if ($received < 0) {
            return 'The received quantity cannot be negative.';
        }

        if ($received > $ordered) {
            return 'The received quantity cannot be more than what was ordered.';
        }

        return null;
    }

    /**
     * Konfirmasi restock diterima (FR-16): stok bertambah sesuai jumlah yang BENAR-BENAR datang,
     * dicatat di log_stok sebagai "masuk", dan status restock menjadi "diterima".
     * Semuanya satu transaksi; klik ganda ditolak karena status sudah bukan "dikirim".
     *
     * @param  array<int|string, mixed>  $received  id_detail => jumlah yang diterima
     */
    public function receiveRestock(int $restockId, array $received): RestockOrder
    {
        return DB::transaction(function () use ($restockId, $received) {
            // kunci baris restock dulu, supaya dua permintaan bersamaan tidak sama-sama menambah stok
            $restock = RestockOrder::lockForUpdate()->findOrFail($restockId);

            if ($restock->status !== 'dikirim') {
                throw new InvalidStatusTransition('Only shipped orders can be confirmed as received.');
            }

            // urut per bahan, sama seperti pemotongan stok, mencegah saling kunci antar transaksi
            $details = $restock->details()->orderBy('id_bahan')->get();

            // periksa semua jumlah SEBELUM mengubah apa pun
            foreach ($details as $detail) {
                $qty = $received[$detail->id_detail] ?? null;

                if ($qty === null || ! is_numeric($qty)) {
                    throw new InvalidArgumentException('Enter the received quantity for every material.');
                }

                $error = self::receiptError((float) $detail->jumlah, (float) $qty);

                if ($error !== null) {
                    throw new InvalidArgumentException($error);
                }
            }

            $now = now();

            foreach ($details as $detail) {
                $qty = round((float) $received[$detail->id_detail], 3);

                $detail->jumlah_diterima = $qty;
                $detail->status = 'diterima';
                $detail->save();

                if ($qty <= 0) {
                    continue; // tidak ada yang datang: stok tidak berubah
                }

                $material = RawMaterial::lockForUpdate()->findOrFail($detail->id_bahan);
                $material->stok_saat_ini = round((float) $material->stok_saat_ini + $qty, 3);
                $material->save();

                StockLog::create([
                    'id_bahan'   => $material->id_bahan,
                    'id_restock' => $restock->id_restock,
                    'id_pesanan' => null,
                    'tipe'       => 'masuk',
                    'jumlah'     => $qty,
                    'waktu'      => $now,
                ]);
            }

            $restock->forceFill(['status' => 'diterima', 'tanggal_diterima' => $now])->save();

            return $restock;
        });
    }

    /**
     * Potong stok untuk satu pesanan. Dipanggil dari dalam transaksi perubahan status,
     * jadi kalau gagal, status pesanan ikut batal. Aman dipanggil dua kali (tidak memotong ganda).
     *
     * @return array<int, array{nama_bahan: string, sisa: float, minimum: float, satuan: string}>
     *         bahan yang BARU menyentuh batas minimum (untuk dikirimi peringatan WhatsApp setelah transaksi selesai)
     */
    public function consumeForOrder(Order $order, User $actor): array
    {
        $low = [];

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

                $low[] = [
                    'nama_bahan' => $material->nama_bahan,
                    'sisa'       => $after,
                    'minimum'    => (float) $material->stok_minimum,
                    'satuan'     => $material->satuan,
                ];
            }
        }

        return $low;
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

    }
}
