<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $cashier = User::where('role', 'kasir')->first() ?? User::create([
            'nama'         => 'Kartiko',
            'username'     => 'kartiko',
            'email'        => 'kartiko@gmail.com',
            'no_hp'        => '081300000001',
            'role'         => 'kasir',
            'password'     => Hash::make('password123'),
            'status_aktif' => true,
        ]);

        // Services and the add-on are created by ServiceSeeder (run it first).
        // firstOrCreate keeps this seeder safe to run on its own too.
        $laundry     = Service::firstOrCreate(['nama_layanan' => 'Laundry'],        ['harga_per_kg' => 8000, 'estimasi_hari' => 3]);
        $iron        = Service::firstOrCreate(['nama_layanan' => 'Just Iron'],      ['harga_per_kg' => 6000, 'estimasi_hari' => 2]);
        $laundryIron = Service::firstOrCreate(['nama_layanan' => 'Laundry + Iron'], ['harga_per_kg' => 8000, 'estimasi_hari' => 3]);

        $fragrance = AdditionalService::firstOrCreate(
            ['nama_tambahan' => 'Premium air freshener'],
            ['harga' => 5000],
        );

        // Same 8 transactions as the Report design (Oct 3–9, 2026)
        // [date, code, customer, service, weight kg, total]
        $rows = [
            ['2026-10-09 11:15:00', 'BNG-261009-0428', 'Siti Rahma',    $laundryIron, 4.5, 41000],
            ['2026-10-09 09:30:00', 'BNG-261009-0427', 'Budi Santoso',  $laundry,     3,   52000],
            ['2026-10-08 14:00:00', 'BNG-261008-0426', 'Rina Amelia',   $laundryIron, 5,   40000],
            ['2026-10-07 10:45:00', 'BNG-261007-0425', 'Andi Pratama',  $iron,        2,   12000],
            ['2026-10-06 16:20:00', 'BNG-261006-0424', 'Dewi Lestari',  $laundryIron, 6,   53000],
            ['2026-10-05 13:10:00', 'BNG-261005-0423', 'Agus Hidayat',  $laundry,     4,   56000],
            ['2026-10-04 15:40:00', 'BNG-261004-0422', 'Maya Putri',    $laundryIron, 3.5, 28000],
            ['2026-10-03 09:05:00', 'BNG-261003-0421', 'Fajar Nugroho', $iron,        2,   12000],
        ];

        foreach ($rows as $i => [$when, $code, $customerName, $service, $weight, $total]) {
            $customer = Customer::create([
                'nama'       => $customerName,
                'no_telepon' => '08572000' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            ]);

            $order = Order::create([
                'kode_pesanan'      => $code,
                'id_pelanggan'      => $customer->id_pelanggan,
                'id_user_kasir'     => $cashier->id_user,
                'id_layanan'        => $service->id_layanan,
                'berat_kg'          => $weight,
                'total_harga'       => $total,
                'status_pembayaran' => 'lunas',
                'status'            => 'selesai',
                'token_tracking'    => Str::random(40),
                'waktu_pesanan'     => $when,
                'waktu_selesai'     => $when,
            ]);

            // The first order includes the add-on. harga_saat_ini is the price at the time
            // of the order, so it can differ from today's price in Service Pricing.
            if ($i === 0) {
                DB::table('pesanan_tambahan')->insert([
                    'id_pesanan'     => $order->id_pesanan,
                    'id_tambahan'    => $fragrance->id_tambahan,
                    'harga_saat_ini' => 10000,
                ]);
            }
        }
    }
}