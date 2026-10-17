<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\RestockOrder;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Owner/Admin (cannot register through the public register form)
        $owner = User::create([
            'nama'         => 'Angga',
            'username'     => 'angga-owner',
            'email'        => 'owner@mamexpress.test',
            'no_hp'        => '081200000000',
            'role'         => 'owner_admin',
            'password'     => Hash::make('password123'),
            'status_aktif' => true,
        ]);

        // Suppliers: each supplier = 1 row in `users` + 1 row in `supplier`
        $aroma = $this->createSupplier(
            'PT Aroma Bersih',
            'aroma@mamexpress.test',
            '081211111111',
            'Jl. Soekarno Hatta, Malang'
        );

        $kemasan = $this->createSupplier(
            'CV Kemasan Jaya',
            'kemasan@mamexpress.test',
            '081222222222',
            'Jl. Raya Singosari, Malang'
        );

        // Raw materials: same as the Figma design
        $items = [
            ['Premium air freshener', 'liters', 2,   5,   $aroma,   '2026-10-09 10:30:00'],
            ['Plastic packaging',     'sheets', 80,  100, $kemasan, '2026-10-09 09:00:00'],
            ['Liquid detergent',      'liters', 18,  10,  $aroma,   '2026-10-09 09:00:00'],
            ['Pelembut pakaian',      'liters', 12,  5,   $aroma,   '2026-10-08 16:00:00'],
            ['Pre-wash detergent',    'liters', 6,   3,   $aroma,   '2026-10-08 16:00:00'],
            ['Thermal labels',        'sheets', 350, 100, $kemasan, '2026-10-07 11:00:00'],
        ];

        $materials = [];

        foreach ($items as [$name, $unit, $stock, $minimum, $supplier, $updatedAt]) {
            $material = RawMaterial::create([
                'nama_bahan'    => $name,
                'satuan'        => $unit,
                'stok_saat_ini' => $stock,
                'stok_minimum'  => $minimum,
                'id_supplier'   => $supplier->id_supplier,
            ]);

            // Force updated_at so it matches the design example
            $material->forceFill(['updated_at' => $updatedAt])->saveQuietly();

            $materials[$name] = $material;
        }

        // Latest usage (the "Latest usage" card)
        StockLog::create([
            'id_bahan' => $materials['Premium air freshener']->id_bahan,
            'tipe'     => 'keluar',
            'jumlah'   => 0.5,
            'waktu'    => '2026-10-09 10:30:00',
        ]);

        StockLog::create([
            'id_bahan' => $materials['Liquid detergent']->id_bahan,
            'tipe'     => 'keluar',
            'jumlah'   => 1,
            'waktu'    => '2026-10-09 09:00:00',
        ]);

        // 1 restock request waiting for confirmation (the "Active requests" card)
        RestockOrder::create([
            'kode_restock'      => 'RS-0001',
            'id_supplier'       => $kemasan->id_supplier,
            'id_user_owner'     => $owner->id_user,
            'status'            => 'diajukan',
            'tanggal_pengajuan' => '2026-10-09 08:00:00',
        ]);
    }

    private function createSupplier(string $name, string $email, string $phone, string $address): Supplier
    {
        $user = User::create([
            'nama'         => $name,
            'username'     => str($name)->slug()->toString(),
            'email'        => $email,
            'no_hp'        => $phone,
            'role'         => 'supplier',
            'password'     => Hash::make('password123'),
            'status_aktif' => true,
        ]);

        return Supplier::create([
            'id_user'       => $user->id_user,
            'nama_supplier' => $name,
            'no_whatsapp'   => $phone,
            'alamat'        => $address,
        ]);
    }
}
