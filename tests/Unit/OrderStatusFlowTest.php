<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\OrderStatusService;
use App\Services\QrCodeService;
use PHPUnit\Framework\TestCase;

class OrderStatusFlowTest extends TestCase
{
    public function test_status_hanya_maju_satu_langkah(): void
    {
        $this->assertSame(Order::STATUS_PROCESSED, OrderStatusService::next(Order::STATUS_QUEUED));
        $this->assertSame(Order::STATUS_PACKED, OrderStatusService::next(Order::STATUS_PROCESSED));
        $this->assertSame(Order::STATUS_DONE, OrderStatusService::next(Order::STATUS_PACKED));
    }

    public function test_status_selesai_tidak_punya_langkah_berikutnya(): void
    {
        $this->assertNull(OrderStatusService::next(Order::STATUS_DONE));
        $this->assertNull(OrderStatusService::next('status_asal_asalan'));
    }

    public function test_processing_boleh_memajukan_sampai_dikemas(): void
    {
        $this->assertTrue(OrderStatusService::allowed('petugas_processing', Order::STATUS_QUEUED));
        $this->assertTrue(OrderStatusService::allowed('petugas_processing', Order::STATUS_PROCESSED));
        $this->assertFalse(OrderStatusService::allowed('petugas_processing', Order::STATUS_PACKED));
    }

    public function test_hanya_kasir_boleh_menyelesaikan_pesanan(): void
    {
        $this->assertTrue(OrderStatusService::allowed('kasir', Order::STATUS_PACKED));
        $this->assertFalse(OrderStatusService::allowed('kasir', Order::STATUS_QUEUED));
        $this->assertFalse(OrderStatusService::allowed('supplier', Order::STATUS_QUEUED));
        $this->assertFalse(OrderStatusService::allowed('owner_admin', Order::STATUS_PACKED));
    }

    public function test_qr_menghasilkan_kotak_dan_ukuran_yang_masuk_akal(): void
    {
        $qr = (new QrCodeService())->rects('http://localhost:8000/processing/orders/BNG-261017-0001');

        $this->assertStringContainsString('<rect', $qr['rects']);
        // versi QR terkecil 21 modul + 2 x 4 modul ruang putih
        $this->assertGreaterThanOrEqual(29, $qr['size']);
    }
}
