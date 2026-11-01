<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Halaman Customer (data dari database)
    public function index()
    {
        $customers = $this->all();

        return view('cashier.customers.index', [
            'customers' => $customers,
            // Nomor yang sudah terdaftar (dipakai untuk cek nomor di form)
            'existing' => array_map(fn ($c) => OrderService::normalizePhone($c['phone']), $customers),
        ]);
    }

    // Tombol "Save Customer"
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $digits = OrderService::normalizePhone($data['whatsapp']);

        if (!preg_match('/^08\d{8,11}$/', $digits)) {
            return back()
                ->withErrors(['whatsapp' => 'Enter a valid Indonesian WhatsApp number, e.g. 0812 3456 7890.'])
                ->withInput();
        }

        if (Customer::where('no_telepon', $digits)->exists()) {
            return back()
                ->withErrors(['whatsapp' => 'This number is already registered.'])
                ->withInput();
        }

        // Catatan (notes) belum punya kolom di tabel pelanggan, jadi belum disimpan.
        Customer::create([
            'nama' => $data['full_name'],
            'no_telepon' => $digits,
        ]);

        return redirect()
            ->route('cashier.customers.index')
            ->with('success', $data['full_name'] . ' has been added to the customer list.');
    }

    // Halaman Customer -> History: ringkasan dan semua pesanan satu pelanggan (terbaru di atas)
    public function history(Customer $customer)
    {
        $orders = $customer->orders()
            ->with('service')
            ->orderByDesc('waktu_pesanan')
            ->orderByDesc('id_pesanan')
            ->get();

        $rows = $orders->map(fn (Order $o) => [
            'code' => $o->kode_pesanan,
            'date' => $o->waktu_pesanan->format('j M Y'),
            'time' => $o->waktu_pesanan->format('H.i'),
            'service' => $o->service->nama_layanan,
            'weight' => Order::kg($o->berat_kg),
            'total' => Order::rupiah($o->total_harga),
            'paid' => $o->is_paid,
            'status' => $o->status,
        ])->all();

        $waNumber = '62' . substr(OrderService::normalizePhone($customer->no_telepon), 1);

        return view('cashier.customers.history', [
            'customer' => [
                'name' => $customer->nama,
                'phone' => OrderService::formatPhone($customer->no_telepon),
                'since' => $customer->created_at?->format('F Y'),
                'wa_url' => 'https://wa.me/' . $waNumber,
            ],
            'summary' => [
                'orders' => $orders->count(),
                'spent' => Order::rupiah($orders->sum('total_harga')),
                'active' => $orders->where('status', '!=', Order::STATUS_DONE)->count(),
                // Pesanan belum lunas: tagihan yang masih harus ditagih ke pelanggan
                'unpaid' => Order::rupiah($orders->filter(fn (Order $o) => ! $o->is_paid)->sum('total_harga')),
            ],
            'orders' => $rows,
            'statuses' => Order::STATUS_LABELS,
        ]);
    }

    // Cek satu nomor WhatsApp (dipanggil dari form New Order): sudah terdaftar atau belum
    public function lookup(Request $request)
    {
        $phone = OrderService::normalizePhone((string) $request->query('phone', ''));

        if (! preg_match('/^08\d{8,11}$/', $phone)) {
            return response()->json(['found' => false]);
        }

        $customer = Customer::withCount('orders')->where('no_telepon', $phone)->first();

        if (! $customer) {
            return response()->json(['found' => false]);
        }

        $since = $customer->created_at ? 'Customer since ' . $customer->created_at->format('F Y') . ' • ' : '';

        return response()->json([
            'found' => true,
            'name' => $customer->nama,
            'meta' => $since . $customer->orders_count . ($customer->orders_count === 1 ? ' order' : ' orders'),
        ]);
    }

    // Semua pelanggan + jumlah pesanan + kunjungan terakhir (pesanan terakhir, atau tanggal didaftarkan)
    private function all(): array
    {
        return Customer::withCount('orders')
            ->withMax('orders', 'waktu_pesanan')
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id_pelanggan,
                'name' => $c->nama,
                'phone' => OrderService::formatPhone($c->no_telepon),
                'orders' => $c->orders_count,
                'last_visit' => Carbon::parse($c->orders_max_waktu_pesanan ?? $c->created_at ?? now()),
            ])
            ->sortByDesc(fn ($c) => $c['last_visit']->timestamp)
            ->values()
            ->all();
    }
}