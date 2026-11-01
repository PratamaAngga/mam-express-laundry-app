<?php

namespace App\Services;

use App\Models\AdditionalService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Logika bisnis pembuatan pesanan (milik backend).
 * Controller cukup memanggil create(); semua aturan harga, kode, dan pencatatan ada di sini.
 */
class OrderService
{
    // Nama layanan tambahan yang dipakai checkbox "freshener" di form kasir (di-seed ServiceSeeder).
    public const FRESHENER_NAME = 'Premium air freshener';

    /**
     * Samakan format nomor telepon: 0857..., +62857..., 62857... -> 0857...
     * Penting karena kolom pelanggan.no_telepon UNIQUE (pelanggan lama dikenali lewat nomor).
     */
    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        return str_starts_with($digits, '0') ? $digits : '0' . $digits;
    }

    // "085720000001" -> "0857 2000 0001" (hanya untuk tampilan)
    public static function formatPhone(string $digits): string
    {
        return trim(preg_replace('/(\d{4})(?=\d)/', '$1 ', $digits));
    }

    // Layanan tambahan pewangi. Kalau belum ada: jalankan `php artisan db:seed --class=ServiceSeeder`.
    public function freshener(): AdditionalService
    {
        return AdditionalService::where('nama_tambahan', self::FRESHENER_NAME)->firstOrFail();
    }

    /**
     * Hitung total: berat x harga per kg (+ harga tambahan kalau dipilih).
     *
     * @return array{service_total: float, addon_total: float, total: float}
     */
    public function calculateTotal(Service $service, float $kg, ?AdditionalService $addon = null): array
    {
        $serviceTotal = round($kg * $service->harga_per_kg);
        $addonTotal = $addon ? (float) $addon->harga : 0.0;

        return [
            'service_total' => $serviceTotal,
            'addon_total'   => $addonTotal,
            'total'         => $serviceTotal + $addonTotal,
        ];
    }

    /**
     * Buat pesanan baru lengkap dengan pelanggan, layanan tambahan, dan riwayat status awal.
     *
     * @param  array{customer_name: string, whatsapp: string, weight: float, service_id: int, freshener: bool}  $data
     */
    public function create(User $cashier, array $data): Order
    {
        $maxAttempts = 3;

        // Kode pesanan harus unik. Kalau dua kasir menyimpan di detik yang sama dan kodenya
        // bentrok, database menolak (UNIQUE) dan kita coba lagi dengan nomor urut berikutnya.
        for ($attempt = 0; ; $attempt++) {
            try {
                return DB::transaction(fn () => $this->store($cashier, $data, $attempt));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt + 1 >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }

    private function store(User $cashier, array $data, int $attempt): Order
    {
        $now = now();
        $service = Service::active()->findOrFail($data['service_id']);
        $addon = $data['freshener'] ? $this->freshener() : null;
        $totals = $this->calculateTotal($service, (float) $data['weight'], $addon);

        // Pelanggan lama dikenali lewat nomor telepon; pelanggan baru langsung didaftarkan.
        $customer = Customer::firstOrCreate(
            ['no_telepon' => self::normalizePhone($data['whatsapp'])],
            ['nama' => $data['customer_name']],
        );

        // BNG-YYMMDD-xxxx, xxxx = urutan pesanan hari itu (mulai 0001)
        $sequence = Order::whereDate('waktu_pesanan', $now->toDateString())->count() + 1 + $attempt;

        $order = Order::create([
            'kode_pesanan'      => 'BNG-' . $now->format('ymd') . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'id_pelanggan'      => $customer->id_pelanggan,
            'id_user_kasir'     => $cashier->id_user,
            'id_layanan'        => $service->id_layanan,
            'berat_kg'          => $data['weight'],
            'total_harga'       => $totals['total'],
            'status_pembayaran' => 'lunas', // keputusan tim: bayar di awal
            'status'            => Order::STATUS_QUEUED,
            'token_tracking'    => Str::random(40),
            'waktu_pesanan'     => $now,
        ]);

        if ($addon) {
            // Simpan harga saat ini, supaya kalau harga berubah nanti pesanan lama tidak ikut berubah.
            $order->additionalServices()->attach($addon->id_tambahan, ['harga_saat_ini' => $addon->harga]);
        }

        OrderStatusHistory::create([
            'id_pesanan'      => $order->id_pesanan,
            'id_user'         => $cashier->id_user,
            'status_lama'     => null,
            'status_baru'     => Order::STATUS_QUEUED,
            'waktu_perubahan' => $now,
        ]);

        return $order->load(['customer', 'service', 'additionalServices']);
    }
}
