<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Same as the Service Pricing design. firstOrCreate avoids duplicates
        // if another seeder (e.g. OrderSeeder) already created a service with the same name.
        $services = [
            ['Laundry',        8000, 3],
            ['Laundry + Iron', 8000, 3],
            ['Just Iron',      6000, 2],
        ];

        foreach ($services as [$name, $price, $days]) {
            Service::firstOrCreate(
                ['nama_layanan' => $name],
                ['harga_per_kg' => $price, 'estimasi_hari' => $days, 'status_aktif' => true],
            );
        }

        AdditionalService::firstOrCreate(
            ['nama_tambahan' => 'Premium air freshener'],
            ['harga' => 5000],
        );
    }
}
