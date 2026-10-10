<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const FRESHENER_PRICE = 5000;

    // Data layanan sementara (nanti diganti dari database oleh tim backend)
    private function services(): array
    {
        return [
            ['code' => 'laundry_iron', 'name' => 'Laundry + Iron',  'price' => 8000,  'days' => 3],
            ['code' => 'iron',         'name' => 'Iron',            'price' => 6000,  'days' => 2],
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
            'whatsapp'      => ['required', 'string', 'max:20'],
            'customer_name' => ['required', 'string', 'max:100'],
            'weight'        => ['required', 'numeric', 'gt:0'],
            'service'       => ['required', Rule::in(array_column($this->services(), 'code'))],
            'payment'       => ['required', Rule::in(['cash_paid', 'cash_later', 'transfer'])],
            'notes'         => ['nullable', 'string', 'max:255'],
        ]);

        $service = collect($this->services())->firstWhere('code', $data['service']);
        $kg = (float) $data['weight'];
        $serviceTotal = $kg * $service['price'];
        $withFreshener = $request->boolean('freshener');
        $freshenerTotal = $withFreshener ? self::FRESHENER_PRICE : 0;

        $now = Carbon::now();
        $eta = $now->copy()->addDays($service['days']);
        $digits = preg_replace('/\D/', '', $data['whatsapp']);
        $paid = $data['payment'] !== 'cash_later';

        // TODO (backend): simpan ke database, buat invoice, kirim WhatsApp.
        // Sementara semua data disimpan di session supaya halaman sukses bisa tampil.
        $order = [
            'code'            => 'BNG-' . $now->format('ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'cashier'         => 'Kartiko',
            'customer_name'   => $data['customer_name'],
            'whatsapp'        => trim(preg_replace('/(\d{4})(?=\d)/', '$1 ', $digits)),
            'service_name'    => $service['name'],
            'price_per_kg'    => $service['price'],
            'weight_label'    => rtrim(rtrim(number_format($kg, 2, ',', ''), '0'), ','),
            'service_total'   => $serviceTotal,
            'freshener'       => $withFreshener,
            'freshener_total' => $freshenerTotal,
            'total'           => $serviceTotal + $freshenerTotal,
            'payment_label'   => $data['payment'] === 'transfer' ? 'Transfer' : 'Cash',
            'paid'            => $paid,
            'notes'           => $data['notes'] ?? null,
            'posted_at'       => $now->format('F j, Y') . ', ' . $this->time($now),
            'sent_at'         => $now->format('j F Y') . ', ' . $now->format('H.i'),
            'eta_long'        => $eta->format('F j, Y') . ', ' . $this->time($eta),
            'eta_label'       => $eta->format('j M Y') . ', ' . $eta->format('H.i'),
            'eta_date_only'   => $eta->format('F j'),
            'eta_time'        => $this->time($eta),
        ];

        session(['last_order' => $order]);

        return redirect()->route('cashier.orders.success');
    }

    // Halaman "Your order has been successfully saved"
    public function success()
    {
        $order = session('last_order');

        // Kalau dibuka langsung tanpa order, kembali ke form
        if (! $order) {
            return redirect()->route('cashier.orders.create');
        }

        return view('cashier.orders.success', ['order' => $order]);
    }

    // 10:42 a.m. / 3:05 p.m.
    private function time(Carbon $date): string
    {
        return $date->format('g:i') . ' ' . ($date->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }
}