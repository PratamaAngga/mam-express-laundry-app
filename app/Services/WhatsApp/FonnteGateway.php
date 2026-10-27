<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Driver "fonnte": mengirim pesan sungguhan lewat API Fonnte. */
class FonnteGateway implements Gateway
{
    public function send(string $to, string $message): SendResult
    {
        $token = config('whatsapp.fonnte.token');

        if (! $token) {
            return SendResult::failed('FONNTE_TOKEN belum diisi di .env');
        }

        try {
            // Token ditulis langsung di header Authorization (tanpa kata "Bearer").
            // Nomor berawalan 0 otomatis diganti kode negara 62 oleh Fonnte.
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout((int) config('whatsapp.fonnte.timeout'))
                ->post(config('whatsapp.fonnte.url'), [
                    'target' => $to,
                    'message' => $message,
                ]);
        } catch (ConnectionException $e) {
            return SendResult::failed('Tidak bisa terhubung ke Fonnte: ' . $e->getMessage());
        }

        $json = $response->json() ?? [];
        // Fonnte menulis kunci "status" (kadang "Status") berisi true/false
        $status = filter_var($json['status'] ?? $json['Status'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // status: true hanya berarti pesan masuk antrean Fonnte, belum tentu sudah sampai ke penerima
        if ($response->successful() && $status) {
            return SendResult::sent();
        }

        return SendResult::failed((string) ($json['reason'] ?? $json['message'] ?? ('HTTP ' . $response->status())));
    }
}
