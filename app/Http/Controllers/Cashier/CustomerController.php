<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

    // Semua pelanggan + jumlah pesanan + kunjungan terakhir (pesanan terakhir, atau tanggal didaftarkan)
    private function all(): array
    {
        return Customer::withCount('orders')
            ->withMax('orders', 'waktu_pesanan')
            ->get()
            ->map(fn (Customer $c) => [
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
