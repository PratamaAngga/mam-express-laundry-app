<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar saat perpindahan status pesanan tidak diizinkan (role salah, status sudah berubah, dll). */
class InvalidStatusTransition extends RuntimeException
{
}
