<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\RestockDetail;
use App\Models\RestockOrder;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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

        // Employees: same as the Employee page design (Lia is inactive)
        $employees = [
            ['Kartiko',        'kartiko@gmail.com',     '081300000001', 'kasir', true],
            ['Rizky Saputra',  'rizky@mamexpress.test', '081300000002', 'kasir', true],
            ['Sari Wulandari', 'sari@mamexpress.test',  '081300000003', 'kasir', true],
            ['Lia Kartika',    'lia@mamexpress.test',   '081300000004', 'kasir', false],
        ];

        foreach ($employees as [$name, $email, $phone, $role, $active]) {
            User::create([
                'nama'         => $name,
                'username'     => str($name)->slug()->toString(),
                'email'        => $email,
                'no_hp'        => $phone,
                'role'         => $role,
                'password'     => Hash::make('password123'),
                'status_aktif' => $active,
            ]);
        }

        // Suppliers: each supplier = 1 row in `users` + 1 row in `supplier`
        $aroma = $this->createSupplier('PT Aroma Bersih', 'Nadia Ananda', 'aroma@mamexpress.test', '081188005500', 'Jl. Soekarno Hatta, Malang');
        $kemasan = $this->createSupplier('CV Kemasan Jaya', 'Dimas Pratama', 'kemasan@mamexpress.test', '081399004400', 'Jl. Raya Singosari, Malang');

        // Raw materials: same as the Figma design
        $items = [
            // [nama, satuan, stok, minimum, supplier, updated_at, harga satuan yang ditetapkan supplier]
            ['Premium air freshener', 'liters', 2,   5,   $aroma,   '2026-10-09 10:30:00', 20000],
            ['Plastic packaging',     'sheets', 80,  100, $kemasan, '2026-10-09 09:00:00', 1000],
            ['Liquid detergent',      'liters', 18,  10,  $aroma,   '2026-10-09 09:00:00', 24000],
            ['Pelembut pakaian',      'liters', 12,  5,   $aroma,   '2026-10-08 16:00:00', 14000],
            ['Pre-wash detergent',    'liters', 6,   3,   $aroma,   '2026-10-08 16:00:00', 16000],
            ['Thermal labels',        'sheets', 350, 100, $kemasan, '2026-10-07 11:00:00', 300],
        ];

        $materials = [];

        foreach ($items as [$name, $unit, $stock, $minimum, $supplier, $updatedAt, $price]) {
            $material = RawMaterial::create([
                'nama_bahan'    => $name,
                'satuan'        => $unit,
                'stok_saat_ini' => $stock,
                'stok_minimum'  => $minimum,
                'id_supplier'   => $supplier->id_supplier,
                'harga_satuan'  => $price,
            ]);

            // Force updated_at so it matches the design example
            $material->forceFill(['updated_at' => $updatedAt])->saveQuietly();

            $materials[$name] = $material;
        }

        // Latest usage (the "Latest usage" card on the inventory page)
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

        // Restock orders (oldest first). Quantities and prices match the Supplier page design.
        $this->createRestock('RST-260920-015', $aroma, $owner, 'diterima', '2026-09-20 09:00:00', [
            [$materials['Pelembut pakaian'], 25, 14000],
        ]);

        $this->createRestock('RST-260928-016', $aroma, $owner, 'diterima', '2026-09-28 09:00:00', [
            [$materials['Liquid detergent'], 30, 24000],
        ]);

        $this->createRestock('RST-261002-017', $kemasan, $owner, 'diterima', '2026-10-02 09:00:00', [
            [$materials['Plastic packaging'], 250, 1000],
        ]);

        $this->createRestock('RST-261009-018', $aroma, $owner, 'diajukan', '2026-10-09 10:30:00', [
            [$materials['Premium air freshener'], 20, 20000],
        ], 'Stock is running low, please make this a priority.');

        $this->createRestock('RST-261009-019', $aroma, $owner, 'dikonfirmasi', '2026-10-09 11:00:00', [
            [$materials['Premium air freshener'], 15, 20000],
        ]);

        $this->createRestock('RST-261009-020', $aroma, $owner, 'dikirim', '2026-10-09 11:10:00', [
            [$materials['Premium air freshener'], 10, 20000],
        ]);

        $this->call(ServiceSeeder::class);
        $this->call(StockUsageSeeder::class);
        $this->call(OrderSeeder::class);
    }

    private function createSupplier(string $companyName, string $contactName, string $email, string $phone, string $address): Supplier
    {
        $user = User::create([
            'nama'         => $contactName,
            'username'     => str($contactName)->slug()->toString(),
            'email'        => $email,
            'no_hp'        => $phone,
            'role'         => 'supplier',
            'password'     => Hash::make('password123'),
            'status_aktif' => true,
        ]);

        return Supplier::create([
            'id_user'       => $user->id_user,
            'nama_supplier' => $companyName,
            'no_whatsapp'   => $phone,
            'alamat'        => $address,
        ]);
    }

    /**
     * @param array<int, array{0: RawMaterial, 1: int|float, 2: int|float}> $lines [material, quantity, unit price]
     */
    private function createRestock(
        string $code,
        Supplier $supplier,
        User $owner,
        string $status,
        string $submittedAt,
        array $lines,
        ?string $note = null,
    ): void {
        $flow = ['diajukan', 'dikonfirmasi', 'dikirim', 'diterima'];
        $reached = array_search($status, $flow);
        $submitted = Carbon::parse($submittedAt);

        $order = RestockOrder::create([
            'kode_restock'         => $code,
            'id_supplier'          => $supplier->id_supplier,
            'id_user_owner'        => $owner->id_user,
            'status'               => $status,
            'catatan'              => $note,
            'perkiraan_kirim'      => $reached >= 1 ? $submitted->copy()->addDay()->setTime(8, 30) : null,
            'catatan_konfirmasi'   => $reached >= 1 ? 'Available. Delivered by internal courier as scheduled.' : null,
            'ekspedisi'            => $reached >= 2 ? 'Internal courier of ' . $supplier->nama_supplier : null,
            'nama_kurir'           => $reached >= 2 ? 'Dimas Saputra' : null,
            'kontak_kurir'         => $reached >= 2 ? '0811 8800 5500' : null,
            'nomor_resi'           => $reached >= 2 ? 'AB-' . $submitted->copy()->addDays(2)->format('ymd') . '-' . substr($code, -3) : null,
            'estimasi_tiba'        => $reached >= 2 ? $submitted->copy()->addDays(2)->setTime(15, 0) : null,
            'catatan_pengiriman'   => $reached >= 2 ? 'Sealed packaging. Please check the quantity when received.' : null,
            'tanggal_pengajuan'    => $submitted,
            'tanggal_dikonfirmasi' => $reached >= 1 ? $submitted->copy()->addDay() : null,
            'tanggal_dikirim'      => $reached >= 2 ? $submitted->copy()->addDays(2) : null,
            'tanggal_diterima'     => $reached >= 3 ? $submitted->copy()->addDays(3) : null,
        ]);

        foreach ($lines as [$material, $quantity, $price]) {
            RestockDetail::create([
                'id_restock'   => $order->id_restock,
                'id_bahan'     => $material->id_bahan,
                'jumlah'       => $quantity,
                'harga_satuan' => $price,
                'status'       => $status,
            ]);
        }
    }
}