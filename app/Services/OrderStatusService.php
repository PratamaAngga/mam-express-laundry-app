<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransition;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aturan perpindahan status pesanan (milik backend).
 * Alur: antre_cuci -> diproses -> dikemas_siap_diambil -> selesai. Hanya maju satu langkah, tidak boleh lompat/mundur.
 */
class OrderStatusService
{
    public function __construct(private StockService $stock, private WhatsAppService $whatsApp)
    {
    }

    // Jeda anti dobel-scan (detik) kalau klien belum mengirim status yang dilihat petugas
    private const COOLDOWN_SECONDS = 15;

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

    public static function stepsFor(string $role): array
    {
        $steps = [];
        foreach (self::FLOW as $from => $rule) {
            if ($rule['role'] === $role) {
                $steps[] = ['from' => $from, 'to' => $rule['to']];
            }
        }

        return $steps;
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

    /** Alasan kenapa role ini tidak bisa memajukan status; null kalau boleh. */
    public static function blockedReason(string $role, string $current): ?string
    {
        if (self::next($current) === null) {
            return 'This order is already done.';
        }

        if (! self::allowed($role, $current)) {
            return $current === Order::STATUS_PACKED
                ? 'This order is packed and waiting at the counter. The cashier marks it as done at pickup.'
                : 'Your role is not allowed to update this status.';
        }

        return null;
    }

    /**
     * Majukan status satu langkah.
     * $expectedFrom = status yang dilihat petugas di layar; kalau di database sudah berubah (mis. dua orang
     * menekan tombol bersamaan) pesanan ditolak, supaya status tidak melompat. Kalau null (klien belum
     * mengirimnya), dipasang jeda singkat anti dobel-scan oleh petugas yang sama.
     */
    public function advance(User $actor, string $kode, ?string $expectedFrom = null): Order
    {
        $lowStock = [];

        $order = DB::transaction(function () use ($actor, $kode, $expectedFrom, &$lowStock) {
            // lockForUpdate: kunci baris selama transaksi, supaya dua permintaan tidak saling menimpa
            $order = Order::where('kode_pesanan', $kode)->lockForUpdate()->firstOrFail();

            if ($expectedFrom !== null && $order->status !== $expectedFrom) {
                $now = Order::STATUS_LABELS[$order->status] ?? $order->status;
                throw new InvalidStatusTransition("This order was just updated (now: {$now}). Reload the page and check again.");
            }

            $reason = self::blockedReason($actor->role, $order->status);
            if ($reason !== null) {
                throw new InvalidStatusTransition($reason);
            }

            if ($expectedFrom === null && $this->updatedRecentlyBy($actor, $order)) {
                throw new InvalidStatusTransition('This order was just updated. Wait a moment before updating it again.');
            }

            $next = self::next($order->status);
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

            // Cucian mulai dicuci: potong stok bahan baku sesuai berat (di transaksi yang sama)
            if ($next === Order::STATUS_PROCESSED) {
                $lowStock = $this->stock->consumeForOrder($order, $actor);
            }

            return $order;
        });

        // WhatsApp dikirim SETELAH transaksi selesai, jadi masalah WhatsApp tidak pernah membatalkan perubahan status
        try {
            $this->whatsApp->sendStatusUpdate($order);

            if ($lowStock !== []) {
                $this->whatsApp->sendLowStockWarning($lowStock);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $order;
    }

    private function updatedRecentlyBy(User $actor, Order $order): bool
    {
        return OrderStatusHistory::where('id_pesanan', $order->id_pesanan)
            ->where('id_user', $actor->id_user)
            ->where('waktu_perubahan', '>=', now()->subSeconds(self::COOLDOWN_SECONDS))
            ->exists();
    }
}
