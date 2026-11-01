<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Mengantar satu pesan WhatsApp yang sudah tercatat di notifikasi_wa. */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Percobaan maksimal; jeda antar percobaan ada di backoff()
    public int $tries = 3;

    public function __construct(public int $messageId)
    {
    }

    /** @return array<int, int> jeda (detik) sebelum percobaan ke-2 dan ke-3 */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WhatsAppService $whatsApp): void
    {
        $message = WhatsappMessage::find($this->messageId);

        // Sudah terkirim/disimulasikan/gagal permanen: jangan kirim dua kali
        if (! $message || ! $message->isPending()) {
            return;
        }

        $whatsApp->deliver($message);
    }

    // Semua percobaan habis
    public function failed(Throwable $e): void
    {
        WhatsappMessage::where('id_notifikasi', $this->messageId)->update([
            'status_kirim' => WhatsappMessage::GAGAL,
            'alasan_gagal' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
