<?php

namespace App\Services\WhatsApp;

/** Satu jalur pengiriman WhatsApp (log, Fonnte, nanti bisa Cloud API). */
interface Gateway
{
    public function send(string $to, string $message): SendResult;
}
