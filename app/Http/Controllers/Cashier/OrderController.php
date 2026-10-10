<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Menampilkan halaman New Order
    public function create()
    {
        // Data layanan sementara (nanti diganti dari database oleh tim backend)
        $services = [
            ['code' => 'laundry_iron', 'name' => 'Laundry + Iron', 'price' => 8000,  'days' => 3],
            ['code' => 'express_24',   'name' => '24-Hour Express', 'price' => 14000, 'days' => 1],
            ['code' => 'iron',         'name' => 'Iron',            'price' => 6000,  'days' => 2],
        ];

        return view('cashier.orders.create', [
            'services' => $services,
            'freshenerPrice' => 5000,
        ]);
    }

    // Menyimpan order (sementara hanya validasi; backend yang melanjutkan)
    public function store(Request $request)
    {
        $request->validate([
            'whatsapp'      => ['required', 'string', 'max:20'],
            'customer_name' => ['required', 'string', 'max:100'],
            'weight'        => ['required'],
            'service'       => ['required', 'string'],
            'payment'       => ['required', 'string'],
        ]);

        // TODO (backend): simpan ke database, buat invoice, kirim WhatsApp
        return redirect()
            ->route('cashier.orders.create')
            ->with('success', 'Order berhasil disimpan.');
    }
}