<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Order;
use App\Models\RestockOrder;
use App\Models\WhatsappMessage;
use RuntimeException;

/**
 * Pintu masuk WhatsApp (milik backend): menulis pesan ke buku catatan (notifikasi_wa) lalu menitipkannya
 * ke antrian. Pengiriman sebenarnya dilakukan job lewat driver yang aktif (log atau fonnte).
 */
class WhatsAppService
{
    public function __construct(private Gateway $gateway)
    {
    }

    // Invoice ke pelanggan saat pesanan dibuat
    public function sendInvoice(Order $order): ?WhatsappMessage
    {
        $to = $this->digits($order->customer->no_telepon);

        if ($to === '') {
            return null;
        }

        return $this->queue(
            WhatsappMessage::TIPE_INVOICE_PELANGGAN,
            $to,
            MessageTemplates::invoice($order),
            orderId: $order->id_pesanan,
        );
    }

    // Permintaan restock ke supplier saat owner menyimpan formulir
    public function sendRestockRequest(RestockOrder $restock): ?WhatsappMessage
    {
        $to = $this->digits($restock->supplier->no_whatsapp);

        if ($to === '') {
            return null;
        }

        return $this->queue(
            WhatsappMessage::TIPE_INVOICE_SUPPLIER,
            $to,
            MessageTemplates::restockRequest($restock),
            restockId: $restock->id_restock,
        );
    }

    // Catat pesan (status pending) lalu titipkan ke antrian; dikirim setelah transaksi database selesai
    public function queue(string $tipe, string $to, string $message, ?int $orderId = null, ?int $restockId = null): WhatsappMessage
    {
        $record = WhatsappMessage::create([
            'tipe'         => $tipe,
            'id_pesanan'   => $orderId,
            'id_restock'   => $restockId,
            'no_tujuan'    => $to,
            'isi_pesan'    => $message,
            'status_kirim' => WhatsappMessage::PENDING,
            'percobaan'    => 0,
        ]);

        SendWhatsAppMessage::dispatch($record->id_notifikasi)->afterCommit();

        return $record;
    }

    /**
     * Satu kali percobaan kirim, dipanggil oleh job. Kalau gagal, melempar exception supaya
     * antrian mencoba ulang; setelah percobaan habis, job menandai pesan "gagal".
     */
    public function deliver(WhatsappMessage $message): void
    {
        $message->increment('percobaan');

        $result = $this->gateway->send($message->no_tujuan, $message->isi_pesan);

        if (! $result->ok) {
            $message->forceFill(['alasan_gagal' => mb_substr($result->reason ?? 'Tidak diketahui', 0, 500)])->save();

            throw new RuntimeException($result->reason ?? 'WhatsApp gagal dikirim');
        }

        $message->forceFill([
            'status_kirim' => $result->simulated ? WhatsappMessage::SIMULASI : WhatsappMessage::TERKIRIM,
            'waktu_kirim'  => now(),
            'alasan_gagal' => null,
        ])->save();

        // Jeda antar pengiriman sungguhan (worker memproses satu pesan satu waktu)
        if (! $result->simulated) {
            sleep(max(0, (int) config('whatsapp.gap_seconds')));
        }
    }

    private function digits(?string $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        return strlen($digits) >= 9 ? $digits : '';
    }
}
