<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\RestockOrder;
use Illuminate\Support\Str;

/** Isi pesan WhatsApp (bahasa Indonesia). Semua teks pesan ada di sini supaya mudah direvisi. */
class MessageTemplates
{
    public static function invoice(Order $order): string
    {
        $shop = config('whatsapp.shop');
        $firstName = Str::of($order->customer->nama)->trim()->before(' ')->toString();
        $addons = $order->additionalServices;
        $eta = $order->waktu_pesanan->copy()->addDays($order->service->estimasi_hari);

        $lines = [
            '*' . $shop['name'] . '*',
            $shop['address'],
            '',
            'Halo ' . $firstName . ', pesanan Anda sudah kami terima ✅',
            '',
            'Kode pesanan: ' . $order->kode_pesanan,
            'Layanan: ' . $order->service->nama_layanan,
            'Berat: ' . Order::kg($order->berat_kg) . ' kg',
        ];

        if ($addons->isNotEmpty()) {
            $lines[] = 'Tambahan: ' . $addons->pluck('nama_tambahan')->implode(', ');
        }

        $lines[] = 'Total: ' . self::rupiah($order->total_harga) . ' (' . ($order->is_paid ? 'lunas' : 'belum lunas') . ')';
        $lines[] = 'Estimasi selesai: ' . self::date($eta);
        $lines[] = '';
        $lines[] = 'Kami akan mengabari setiap status cucian berubah. Terima kasih 🙏';
        $lines[] = '_Pesan otomatis, mohon tidak dibalas._';

        return implode("\n", $lines);
    }

    public static function restockRequest(RestockOrder $restock): string
    {
        $shop = config('whatsapp.shop');

        $lines = [
            '*' . $shop['name'] . '*',
            'Permintaan restock bahan baku',
            '',
            'Yth. ' . $restock->supplier->nama_supplier . ',',
            'Kami ingin memesan:',
            '',
        ];

        foreach ($restock->details as $detail) {
            $material = $detail->material;
            $lines[] = '• ' . $material->nama_bahan . ': ' . RawMaterial::formatNumber($detail->jumlah) . ' ' . $material->satuan
                . ' × ' . self::rupiah($detail->harga_satuan);
        }

        $lines[] = '';
        $lines[] = 'Perkiraan total: ' . self::rupiah($restock->total);
        $lines[] = 'Kode restock: ' . $restock->kode_restock;

        if ($restock->catatan) {
            $lines[] = 'Catatan: ' . $restock->catatan;
        }

        $lines[] = 'Alamat pengiriman: ' . $shop['address'];
        $lines[] = '';
        $lines[] = 'Mohon konfirmasi pesanan ini lewat aplikasi (login sebagai supplier). Terima kasih 🙏';

        return implode("\n", $lines);
    }

    public static function rupiah(float|int|string $amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }

    // 28 Okt 2026, 10.00 (nama bulan Indonesia, tidak bergantung pada locale aplikasi)
    public static function date(\DateTimeInterface $date): string
    {
        $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y') . ', ' . $date->format('H.i');
    }
}
