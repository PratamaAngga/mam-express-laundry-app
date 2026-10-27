<?php

namespace Tests\Feature;

use App\Models\AdditionalService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\WhatsApp\MessageTemplates;
use Tests\TestCase;

class MessageTemplatesTest extends TestCase
{
    public function test_invoice_memuat_data_pesanan_dan_identitas_toko(): void
    {
        config(['whatsapp.shop.name' => 'Mam Express Laundry']);

        $order = (new Order())->forceFill([
            'kode_pesanan' => 'BNG-261025-0001',
            'berat_kg' => 4.5,
            'total_harga' => 41000,
            'status_pembayaran' => 'lunas',
            'waktu_pesanan' => '2026-10-25 10:00:00',
        ]);
        $order->setRelation('customer', (new Customer())->forceFill(['nama' => 'Siti Rahma']));
        $order->setRelation('service', (new Service())->forceFill(['nama_layanan' => 'Laundry + Iron', 'estimasi_hari' => 3]));
        $order->setRelation('additionalServices', collect([
            (new AdditionalService())->forceFill(['nama_tambahan' => 'Premium air freshener']),
        ]));

        $text = MessageTemplates::invoice($order);

        $this->assertStringContainsString('*Mam Express Laundry*', $text);
        $this->assertStringContainsString('Halo Siti,', $text);
        $this->assertStringContainsString('Kode pesanan: BNG-261025-0001', $text);
        $this->assertStringContainsString('Tambahan: Premium air freshener', $text);
        $this->assertStringContainsString('Total: Rp41.000 (lunas)', $text);
        $this->assertStringContainsString('Estimasi selesai: 28 Okt 2026, 10.00', $text);
    }

    public function test_format_rupiah_dan_tanggal(): void
    {
        $this->assertSame('Rp1.250.000', MessageTemplates::rupiah(1250000));
        $this->assertSame('5 Mei 2026, 09.05', MessageTemplates::date(new \DateTime('2026-05-05 09:05:00')));
    }
}
