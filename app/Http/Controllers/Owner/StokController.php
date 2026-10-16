<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

class StokController extends Controller
{
    public function index()
    {
        $bahan = collect([
            ['nama' => 'Premium air freshener', 'stok' => 2,   'minimum' => 5,   'satuan' => 'liters', 'supplier' => 'PT Aroma Bersih', 'update' => 'Oct. 9, 2026, 10:30 a.m.'],
            ['nama' => 'Plastic packaging',     'stok' => 80,  'minimum' => 100, 'satuan' => 'sheets', 'supplier' => 'CV Kemasan Jaya', 'update' => 'Oct. 9, 2026, 9:00 a.m.'],
            ['nama' => 'Liquid detergent',      'stok' => 18,  'minimum' => 10,  'satuan' => 'liters', 'supplier' => 'PT Aroma Bersih', 'update' => 'Oct. 9, 2026, 9:00 a.m.'],
            ['nama' => 'Pelembut pakaian',      'stok' => 12,  'minimum' => 5,   'satuan' => 'liters', 'supplier' => 'PT Aroma Bersih', 'update' => 'Oct. 8, 2026, 4:00 p.m.'],
            ['nama' => 'Pre-wash detergent',    'stok' => 6,   'minimum' => 3,   'satuan' => 'liters', 'supplier' => 'PT Aroma Bersih', 'update' => 'Oct. 8, 2026, 4:00 p.m.'],
            ['nama' => 'Thermal labels',        'stok' => 350, 'minimum' => 100, 'satuan' => 'sheets', 'supplier' => 'CV Kemasan Jaya', 'update' => 'Oct. 7, 2026, 11:00 a.m.'],
        ])->map(fn ($b) => $b + ['menipis' => $b['stok'] < $b['minimum']]);

        $menipis = $bahan->where('menipis', true);

        $ringkasan = [
            'total'    => $bahan->count(),
            'aman'     => $bahan->where('menipis', false)->count(),
            'menipis'  => $menipis->count(),
            'permintaan_aktif' => 1,
        ];

        $pemakaianTerbaru = [
            ['nama' => 'Pewangi premium', 'jam' => '10.30', 'jumlah' => '-0,5 liters'],
            ['nama' => 'Detergen cair',   'jam' => '09.00', 'jumlah' => '-1 liters'],
        ];

        return view('owner.stok.index', compact('bahan', 'menipis', 'ringkasan', 'pemakaianTerbaru'));
    }
}
