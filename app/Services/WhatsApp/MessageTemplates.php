<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\RestockOrder;
use Illuminate\Support\Str;

/** Isi pesan WhatsApp (bahasa Indonesia). Semua teks pesan ada di sini supaya mudah direvisi. */
class MessageTemplates
{
    // Invoice pesanan: rincian layanan, harga, berat, waktu pesanan dibuat, dan kode pesanan (sesuai proposal)
    public static function invoice(Order $order): string
    {
        $shop = config('whatsapp.shop');
        $eta = self::eta($order);

        $lines = [
            '*' . $shop['name'] . '*',
            $shop['address'],
            '',
            'Halo ' . self::firstName($order) . ', pesanan Anda sudah kami terima ✅',
            '',
            'Kode pesanan: ' . $order->kode_pesanan,
            'Waktu pesanan: ' . self::date($order->waktu_pesanan),
            'Layanan: ' . $order->service->nama_layanan . ' (' . self::rupiah($order->service->harga_per_kg) . '/kg)',
            'Berat: ' . Order::kg($order->berat_kg) . ' kg',
        ];

        foreach ($order->additionalServices as $addon) {
            $lines[] = 'Tambahan: ' . $addon->nama_tambahan . ' (' . self::rupiah($addon->pivot->harga_saat_ini) . ')';
        }

        $lines[] = 'Total: ' . self::rupiah($order->total_harga) . ' (' . ($order->is_paid ? 'lunas' : 'belum lunas') . ')';
        $lines[] = 'Estimasi selesai: ' . self::date($eta);
        $lines[] = '';
        $lines[] = 'Kami akan mengabari setiap status cucian berubah. Terima kasih 🙏';
        $lines[] = '_Pesan otomatis, mohon tidak dibalas._';

        return implode("\n", $lines);
    }

    // Kabar perubahan status ke pelanggan (FR-10). Status awal sudah diwakili invoice, jadi tidak ada pesan untuk itu.
    public static function statusUpdate(Order $order): ?string
    {
        $shop = config('whatsapp.shop');
        $name = self::firstName($order);

        $body = match ($order->status) {
            Order::STATUS_PROCESSED => [
                'Halo ' . $name . ', cucian Anda (kode ' . $order->kode_pesanan . ') sekarang sedang kami proses 🧺',
                'Estimasi selesai: ' . self::date(self::eta($order)),
            ],
            Order::STATUS_PACKED => [
                'Halo ' . $name . ', cucian Anda (kode ' . $order->kode_pesanan . ') sudah selesai dan siap diambil ✅',
                'Silakan ambil di ' . $shop['address'] . '. Kami buka 24 jam.',
            ],
            Order::STATUS_DONE => [
                'Halo ' . $name . ', pesanan ' . $order->kode_pesanan . ' sudah diserahkan. Terima kasih telah mencuci di ' . $shop['name'] . ' 🙏',
            ],
            default => null,
        };

        if ($body === null) {
            return null;
        }

        return implode("\n", ['*' . $shop['name'] . '*', '', ...$body, '', '_Pesan otomatis, mohon tidak dibalas._']);
    }

    // Permintaan restock ke supplier (FR-14), lengkap dengan tautan ke daftar permintaan restock
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
        $lines[] = 'Lihat dan konfirmasi permintaan ini (login sebagai supplier):';
        $lines[] = route('supplier.restock.index');

        return implode("\n", $lines);
    }

    /**
     * Peringatan stok menyentuh batas minimum, ke owner (FR-12).
     *
     * @param  array<int, array{nama_bahan: string, sisa: float, minimum: float, satuan: string}>  $items
     */
    public static function lowStock(array $items): string
    {
        $shop = config('whatsapp.shop');

        $lines = ['⚠️ *Stok menipis - ' . $shop['name'] . '*', ''];

        foreach ($items as $item) {
            $lines[] = '• ' . $item['nama_bahan'] . ': sisa ' . RawMaterial::formatNumber($item['sisa']) . ' ' . $item['satuan']
                . ' (batas minimum ' . RawMaterial::formatNumber($item['minimum']) . ' ' . $item['satuan'] . ')';
        }

        $lines[] = '';
        $lines[] = 'Segera ajukan restock:';
        $lines[] = route('owner.restock.create');

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

    private static function firstName(Order $order): string
    {
        return Str::of($order->customer->nama)->trim()->before(' ')->toString();
    }

    private static function eta(Order $order): \DateTimeInterface
    {
        return $order->waktu_pesanan->copy()->addDays($order->service->estimasi_hari);
    }
}
