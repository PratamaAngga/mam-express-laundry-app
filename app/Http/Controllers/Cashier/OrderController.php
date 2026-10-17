<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const FRESHENER_PRICE = 5000;

    private const STATUSES = [
        'accepted' => 'Accepted',
        'washed' => 'Washed',
        'dried' => 'Dried',
        'ready' => 'Ready for pickup',
        'done' => 'Done',
    ];

    // Data layanan d
    private function services(): array
    {
        return [
            ['code' => 'laundry_iron', 'name' => 'Laundry + Iron', 'price' => 8000, 'days' => 3],
            ['code' => 'iron', 'name' => 'Iron', 'price' => 6000, 'days' => 2],
        ];
    }

    // Halaman New Order
    public function create()
    {
        return view('cashier.orders.create', [
            'services' => $this->services(),
            'freshenerPrice' => self::FRESHENER_PRICE,
        ]);
    }

    // Tombol Save Order
    public function store(Request $request)
    {
        // Ubah "4,5" menjadi "4.5" sebelum divalidasi
        $request->merge(['weight' => str_replace(',', '.', (string) $request->input('weight'))]);

        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'max:20'],
            'customer_name' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'gt:0'],
            'service' => ['required', Rule::in(array_column($this->services(), 'code'))],
            'payment' => ['required', Rule::in(['cash_paid', 'transfer'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $service = collect($this->services())->firstWhere('code', $data['service']);
        $kg = (float) $data['weight'];
        $serviceTotal = $kg * $service['price'];
        $withFreshener = $request->boolean('freshener');
        $freshenerTotal = $withFreshener ? self::FRESHENER_PRICE : 0;

        $now = Carbon::now();
        $eta = $now->copy()->addDays($service['days']);
        $digits = preg_replace('/\D/', '', $data['whatsapp']);
        $paid = true; // keputusan tim: pembayaran selalu di awal, pesanan langsung lunas

        // TODO (backend): simpan ke database, buat invoice, kirim WhatsApp.
        // Sementara semua data disimpan di session supaya halaman sukses bisa tampil.
        $order = [
            'code' => 'BNG-' . $now->format('ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'cashier' => auth()->user()->nama,
            'customer_name' => $data['customer_name'],
            'whatsapp' => trim(preg_replace('/(\d{4})(?=\d)/', '$1 ', $digits)),
            'service_name' => $service['name'],
            'price_per_kg' => $service['price'],
            'weight_label' => rtrim(rtrim(number_format($kg, 2, ',', ''), '0'), ','),
            'service_total' => $serviceTotal,
            'freshener' => $withFreshener,
            'freshener_total' => $freshenerTotal,
            'total' => $serviceTotal + $freshenerTotal,
            'payment_label' => $data['payment'] === 'transfer' ? 'Transfer' : 'Cash',
            'paid' => $paid,
            'notes' => $data['notes'] ?? null,
            'posted_at' => $now->format('F j, Y') . ', ' . $this->time($now),
            'sent_at' => $now->format('j F Y') . ', ' . $now->format('H.i'),
            'eta_long' => $eta->format('F j, Y') . ', ' . $this->time($eta),
            'eta_label' => $eta->format('j M Y') . ', ' . $eta->format('H.i'),
            'eta_date_only' => $eta->format('F j'),
            'eta_time' => $this->time($eta),
        ];

        session(['last_order' => $order]);

        return redirect()->route('cashier.orders.success');
    }

    // Halaman "Your order has been successfully saved"
    public function success()
    {
        $order = session('last_order');

        // Kalau dibuka langsung tanpa order, kembali ke form
        if (!$order) {
            return redirect()->route('cashier.orders.create');
        }

        return view('cashier.orders.success', ['order' => $order]);
    }

    // Halaman Order List
    public function index()
    {
        $orders = $this->sampleOrders();
        $count = fn(array $keys) => count(array_filter($orders, fn($o) => in_array($o['status'], $keys, true)));

        return view('cashier.orders.index', [
            'orders' => $orders,
            'statuses' => self::STATUSES,
            'counts' => [
                'all' => count($orders),
                'processing' => $count(['accepted', 'washed', 'dried']),
                'ready' => $count(['ready']),
                'done' => $count(['done']),
            ],
        ]);
    }

    // DATA Dummy untuk Order List
    private function sampleOrders(): array
    {
        return [
            ['code' => 'BNG-261009-0428', 'date' => '9 Oct 2026', 'time' => '10.42', 'name' => 'Siti Rahma', 'phone' => '0858 5617 0561', 'weight' => '4,5', 'service' => 'Laundry + Iron', 'status' => 'accepted'],
            ['code' => 'BNG-261009-0427', 'date' => '9 Oct 2026', 'time' => '09.15', 'name' => 'Budi Santoso', 'phone' => '0813 7788 9900', 'weight' => '3', 'service' => 'Laundry + Iron', 'status' => 'washed'],
            ['code' => 'BNG-261008-0426', 'date' => '8 Oct 2026', 'time' => '14.20', 'name' => 'Rina Amelia', 'phone' => '0857 2211 4400', 'weight' => '5', 'service' => 'Laundry + Iron', 'status' => 'dried'],
            ['code' => 'BNG-261007-0425', 'date' => '7 Oct 2026', 'time' => '11.30', 'name' => 'Andi Pratama', 'phone' => '0821 6611 2200', 'weight' => '2', 'service' => 'Iron', 'status' => 'ready'],
            ['code' => 'BNG-261006-0424', 'date' => '6 Oct 2026', 'time' => '10.10', 'name' => 'Dewi Lestari', 'phone' => '0812 8800 3311', 'weight' => '6', 'service' => 'Laundry + Iron', 'status' => 'done'],
            ['code' => 'BNG-261005-0423', 'date' => '5 Oct 2026', 'time' => '15.45', 'name' => 'Agus Hidayat', 'phone' => '0819 5522 1100', 'weight' => '4', 'service' => 'Laundry + Iron', 'status' => 'ready'],
            ['code' => 'BNG-261004-0422', 'date' => '4 Oct 2026', 'time' => '12.05', 'name' => 'Maya Putri', 'phone' => '0856 9900 2200', 'weight' => '3,5', 'service' => 'Laundry + Iron', 'status' => 'done'],
            ['code' => 'BNG-261003-0421', 'date' => '3 Oct 2026', 'time' => '09.40', 'name' => 'Fajar Nugroho', 'phone' => '0822 4488 6600', 'weight' => '2', 'service' => 'Iron', 'status' => 'done'],
        ];
    }

    // 10:42 a.m. / 3:05 p.m.
    private function time(Carbon $date): string
    {
        return $date->format('g:i') . ' ' . ($date->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }
}