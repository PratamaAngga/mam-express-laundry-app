<?php

namespace Tests\Unit;

use App\Services\StockService;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    public function test_kebutuhan_bahan_dihitung_dari_berat_dan_laju_pemakaian(): void
    {
        // deterjen 10 ml/kg = 0,010 liter/kg; pewangi 5 ml/kg = 0,005 liter/kg
        $this->assertSame(0.045, StockService::amount(4.5, 0.010));
        $this->assertSame(0.1, StockService::amount(10, 0.010));
        $this->assertSame(0.015, StockService::amount(3, 0.005));
    }

    public function test_stok_dianggap_menipis_saat_menyentuh_atau_di_bawah_batas_minimum(): void
    {
        $this->assertTrue(StockService::isLow(9.99, 10));
        $this->assertTrue(StockService::isLow(10, 10));
        $this->assertFalse(StockService::isLow(10.01, 10));
        $this->assertFalse(StockService::isLow(18, 10));
    }

    public function test_peringatan_hanya_saat_stok_menyeberang_ke_batas_atau_di_bawahnya(): void
    {
        // aman -> melewati batas: kirim peringatan
        $this->assertTrue(StockService::crossedIntoLow(10.02, 9.97, 10));
        // aman -> tepat menyentuh batas: kirim peringatan
        $this->assertTrue(StockService::crossedIntoLow(10.05, 10.0, 10));
        // sudah di batas atau di bawahnya lalu turun lagi: jangan kirim ulang
        $this->assertFalse(StockService::crossedIntoLow(10.0, 9.95, 10));
        $this->assertFalse(StockService::crossedIntoLow(9.9, 9.85, 10));
        // masih aman: tidak ada peringatan
        $this->assertFalse(StockService::crossedIntoLow(18, 17.95, 10));
    }
}
