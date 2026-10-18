<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransition;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Aturan perpindahan status pesanan (milik backend).
 * Alur: antre_cuci -> diproses -> dikemas_siap_diambil -> selesai. Hanya maju satu langkah, tidak boleh lompat/mundur.
 */
class OrderStatusService
{
    // status sekarang => status berikutnya + role yang berhak memajukannya
    private const FLOW = [
        Order::STATUS_QUEUED    => ['to' => Order::STATUS_PROCESSED, 'role' => 'petugas_processing'],
        Order::STATUS_PROCESSED => ['to' => Order::STATUS_PACKED,    'role' => 'petugas_processing'],
        Order::STATUS_PACKED    => ['to' => Order::STATUS_DONE,      'role' => 'kasir'],
    ];

    public static function next(string $current): ?string
    {
        return self::FLOW[$current]['to'] ?? null;
    }

    public static function roleFor(string $current): ?string
    {
        return self::FLOW[$current]['role'] ?? null;
    }

    // Apakah role ini boleh memajukan pesanan yang statusnya $current?
    public static function allowed(string $role, string $current): bool
    {
        return self::roleFor($current) === $role;
    }

    /**
     * Majukan status satu langkah. $expectedFrom = status yang dilihat petugas di layar; kalau di database
     * sudah berubah (mis. dua orang menekan tombol bersamaan) pesanan ditolak, supaya status tidak melompat.
     */
    public function advance(User $actor, string $kode, string $expectedFrom): Order
    {
        return DB::transaction(function () use ($actor, $kode, $expectedFrom) {
            // lockForUpdate: kunci baris selama transaksi, supaya dua permintaan tidak saling menimpa
            $order = Order::where('kode_pesanan', $kode)->lockForUpdate()->firstOrFail();

            if ($order->status !== $expectedFrom) {
                $now = Order::STATUS_LABELS[$order->status] ?? $order->status;
                throw new InvalidStatusTransition("This order was just updated (now: {$now}). Reload the page and check again.");
            }

            $next = self::next($order->status);

            if ($next === null) {
                throw new InvalidStatusTransition('This order has no further status.');
            }

            if (! self::allowed($actor->role, $order->status)) {
                throw new InvalidStatusTransition('Your role is not allowed to update this status.');
            }

            $old = $order->status;
            $now = now();

            $order->status = $next;
            if ($next === Order::STATUS_DONE) {
                $order->waktu_selesai = $now;
            }
            $order->save();

            OrderStatusHistory::create([
                'id_pesanan'      => $order->id_pesanan,
                'id_user'         => $actor->id_user,
                'status_lama'     => $old,
                'status_baru'     => $next,
                'waktu_perubahan' => $now,
            ]);

            return $order;
        });
    }
}
