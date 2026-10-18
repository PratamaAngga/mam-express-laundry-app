<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/** Membuat QR asli. Hasilnya berupa kumpulan <rect> SVG (tanpa file gambar, tanpa ekstensi GD/Imagick). */
class QrCodeService
{
    // Ruang putih di sekeliling QR (standar QR: 4 modul) supaya mudah dipindai kamera
    private const QUIET_ZONE = 4;

    /**
     * @return array{rects: string, size: int} rects = isi <g> SVG, size = lebar/tinggi viewBox
     */
    public function rects(string $content): array
    {
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M(), Encoder::DEFAULT_BYTE_MODE_ENCODING, null, false)->getMatrix();
        $width = $matrix->getWidth();
        $rects = '';

        for ($y = 0; $y < $width; $y++) {
            // gabungkan modul hitam yang berurutan dalam satu baris menjadi satu <rect>
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }
                $start = $x;
                while ($x + 1 < $width && $matrix->get($x + 1, $y) === 1) {
                    $x++;
                }
                $rects .= '<rect x="' . ($start + self::QUIET_ZONE) . '" y="' . ($y + self::QUIET_ZONE)
                    . '" width="' . ($x - $start + 1) . '" height="1"/>';
            }
        }

        return ['rects' => $rects, 'size' => $width + 2 * self::QUIET_ZONE];
    }
}
