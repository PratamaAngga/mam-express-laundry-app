<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/** Driver "log": pesan hanya dicatat ke file log, TIDAK dikirim ke siapa pun. */
class LogGateway implements Gateway
{
    public function send(string $to, string $message): SendResult
    {
        Log::info("[WhatsApp simulasi] ke {$to}\n{$message}");

        return SendResult::simulated();
    }
}
