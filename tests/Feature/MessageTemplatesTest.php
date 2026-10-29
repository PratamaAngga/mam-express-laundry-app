<?php

namespace Tests\Feature;

use App\Models\AdditionalService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\WhatsApp\MessageTemplates;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\TestCase;

class MessageTemplatesTest extends TestCase
{
    private function makeOrder(string $status = Order::STATUS_QUEUED): Order
    {
        config([
            'whatsapp.shop.name' => 'Mam Express Laundry',
            'whatsapp.shop.address' => 'Jl. Candi Panggung Bar. No. 61, Mojolangu, Kec. Lowokwaru, Kota Malang',
        ]);

        $order = (new Order())->forceFill([
            'kode_pesanan' => 'BNG-261025-0001',
            'berat_kg' => 4.5,
            'total_harga' => 41000,
            'status' => $status,
            'status_pembayaran' => 'lunas',
            'waktu_pesanan' => '2026-10-25 10:00:00',
        ]);
        $order->setRelation('customer', (new Customer())->forceFill(['nama' => 'Siti Rahma']));
        $order->setRelation('service', (new Service())->forceFill([
            'nama_layanan' => 'Laundry + Iron',
            'harga_per_kg' => 8000,
            'estimasi_hari' => 3,
        ]));

        $addon = (new AdditionalService())->forceFill(['nama_tambahan' => 'Premium air freshener']);
        $addon->setRelation('pivot', (new Pivot())->forceFill(['harga_saat_ini' => 5000]));
        $order->setRelation('additionalServices', collect([$addon]));

        return $order;
    }

    public function test_invoice_memuat_semua_data_yang_diminta_proposal(): void
    {
        $text = MessageTemplates::invoice($this->makeOrder());

        $this->assertStringContainsString('*Mam Express Laundry*', $text);
        $this->assertStringContainsString('Halo Siti,', $text);
        $this->assertStringContainsString('Kode pesanan: BNG-261025-0001', $text);
        $this->assertStringContainsString('Waktu pesanan: 25 Okt 2026, 10.00', $text);
        $this->assertStringContainsString('Layanan: Laundry + Iron (Rp8.000/kg)', $text);
        $this->assertStringContainsString('Berat: 4,5 kg', $text);
        $this->assertStringContainsString('Tambahan: Premium air freshener (Rp5.000)', $text);
        $this->assertStringContainsString('Total: Rp41.000 (lunas)', $text);
        $this->assertStringContainsString('Estimasi selesai: 28 Okt 2026, 10.00', $text);
    }

    public function test_pesan_status_untuk_tiap_perubahan_dan_kosong_untuk_status_awal(): void
    {
        $this->assertNull(MessageTemplates::statusUpdate($this->makeOrder(Order::STATUS_QUEUED)));

        $processed = MessageTemplates::statusUpdate($this->makeOrder(Order::STATUS_PROCESSED));
        $this->assertStringContainsString('sedang kami proses', $processed);

        $packed = MessageTemplates::statusUpdate($this->makeOrder(Order::STATUS_PACKED));
        $this->assertStringContainsString('siap diambil', $packed);
        $this->assertStringContainsString('Jl. Candi Panggung Bar. No. 61', $packed);

        $done = MessageTemplates::statusUpdate($this->makeOrder(Order::STATUS_DONE));
        $this->assertStringContainsString('sudah diserahkan', $done);
    }

    public function test_peringatan_stok_memuat_bahan_dan_tautan_formulir_restock(): void
    {
        config(['whatsapp.shop.name' => 'Mam Express Laundry']);

        $text = MessageTemplates::lowStock([
            ['nama_bahan' => 'Liquid detergent', 'sisa' => 9.97, 'minimum' => 10.0, 'satuan' => 'liters'],
        ]);

        $this->assertStringContainsString('Stok menipis', $text);
        $this->assertStringContainsString('Liquid detergent: sisa 9,97 liters (batas minimum 10 liters)', $text);
        $this->assertStringContainsString(route('owner.restock.create'), $text);
    }

    public function test_format_rupiah_dan_tanggal(): void
    {
        $this->assertSame('Rp1.250.000', MessageTemplates::rupiah(1250000));
        $this->assertSame('5 Mei 2026, 09.05', MessageTemplates::date(new \DateTime('2026-05-05 09:05:00')));
    }
}
