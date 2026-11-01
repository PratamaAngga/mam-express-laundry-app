<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use App\Models\RawMaterial;
use Illuminate\Database\Seeder;

/**
 * Laju pemakaian bahan baku per kg cucian (keputusan tim):
 * deterjen 10 ml/kg untuk semua pesanan, pewangi 5 ml/kg hanya untuk pesanan dengan layanan tambahan pewangi.
 * Aman dijalankan berulang: php artisan db:seed --class=StockUsageSeeder
 */
class StockUsageSeeder extends Seeder
{
    public function run(): void
    {
        $this->setRate('Liquid detergent', 10, null);

        $freshener = AdditionalService::where('nama_tambahan', 'Premium air freshener')->first();

        if ($freshener) {
            $this->setRate('Premium air freshener', 5, $freshener->id_tambahan);
        } else {
            $this->command?->warn('Layanan tambahan pewangi belum ada. Jalankan ServiceSeeder dulu.');
        }
    }

    private function setRate(string $name, float $mlPerKg, ?int $triggerAddonId): void
    {
        $material = RawMaterial::where('nama_bahan', $name)->first();

        if (! $material) {
            $this->command?->warn("Bahan baku '{$name}' tidak ditemukan.");
            return;
        }

        // Ubah ml menjadi satuan bahan itu (liter -> x 0,001)
        $factor = match (strtolower(trim($material->satuan))) {
            'l', 'liter', 'liters', 'litre', 'litres' => 0.001,
            'ml' => 1.0,
            default => null,
        };

        if ($factor === null) {
            $this->command?->warn("Satuan '{$material->satuan}' pada '{$name}' belum dikenali, laju pemakaian tidak diisi.");
            return;
        }

        $material->forceFill([
            'pemakaian_per_kg'   => $mlPerKg * $factor,
            'id_tambahan_pemicu' => $triggerAddonId,
        ])->save();
    }
}
