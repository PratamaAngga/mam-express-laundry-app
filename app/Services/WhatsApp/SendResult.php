<?php

namespace App\Services\WhatsApp;

/** Hasil satu kali percobaan kirim: terkirim ke gateway, disimulasikan (driver log), atau gagal. */
final class SendResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly bool $simulated = false,
        public readonly ?string $reason = null,
    ) {
    }

    public static function sent(): self
    {
        return new self(true);
    }

    public static function simulated(): self
    {
        return new self(true, true);
    }

    public static function failed(string $reason): self
    {
        return new self(false, false, $reason);
    }
}
