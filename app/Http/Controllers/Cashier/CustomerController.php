<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Halaman Customer
    public function index()
    {
        $customers = $this->all();

        return view('cashier.customers.index', [
            'customers' => $customers,
            // Nomor yang sudah terdaftar (dipakai untuk cek nomor di form)
            'existing' => array_map(fn($c) => $this->normalize($c['phone']), $customers),
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

        $digits = $this->normalize($data['whatsapp']);

        if (!preg_match('/^08\d{8,11}$/', $digits)) {
            return back()
                ->withErrors(['whatsapp' => 'Enter a valid Indonesian WhatsApp number, e.g. 0812 3456 7890.'])
                ->withInput();
        }

        foreach ($this->all() as $customer) {
            if ($this->normalize($customer['phone']) === $digits) {
                return back()
                    ->withErrors(['whatsapp' => 'This number is already registered.'])
                    ->withInput();
            }
        }

        // TODO (backend): simpan ke database.
        // Sementara disimpan di session supaya langsung muncul di daftar.
        $extra = session('customers_extra', []);
        $extra[] = [
            'name' => $data['full_name'],
            'phone' => $this->format($digits),
            'orders' => 0,
            'last_visit' => Carbon::now()->format('Y-m-d H:i:s'),
            'notes' => $data['notes'] ?? null,
        ];
        session(['customers_extra' => $extra]);

        return redirect()
            ->route('cashier.customers.index')
            ->with('success', $data['full_name'] . ' has been added to the customer list.');
    }

    private function all(): array
    {
        $rows = [
            ['Siti Rahma', '0858 5617 0561', 12, '2026-10-09'],
            ['Budi Santoso', '0813 7788 9900', 7, '2026-10-09'],
            ['Rina Amelia', '0857 2211 4400', 5, '2026-10-08'],
            ['Andi Pratama', '0821 6611 2200', 9, '2026-10-07'],
            ['Dewi Lestari', '0812 8800 3311', 3, '2026-10-06'],
            ['Agus Hidayat', '0819 5522 1100', 6, '2026-10-05'],
            ['Maya Putri', '0856 9900 2200', 4, '2026-10-04'],
            ['Fajar Nugroho', '0822 4488 6600', 2, '2026-10-03'],
        ];

        $customers = array_map(fn($r) => [
            'name' => $r[0],
            'phone' => $r[1],
            'orders' => $r[2],
            'last_visit' => Carbon::parse($r[3]),
        ], $rows);

        foreach (session('customers_extra', []) as $extra) {
            $customers[] = [
                'name' => $extra['name'],
                'phone' => $extra['phone'],
                'orders' => $extra['orders'],
                'last_visit' => Carbon::parse($extra['last_visit']),
            ];
        }

        // Urutkan dari kunjungan terbaru
        usort($customers, fn($a, $b) => $b['last_visit']->timestamp <=> $a['last_visit']->timestamp);

        return $customers;
    }

    // "+62 812-3456-7890" / "0812 3456 7890" -> "081234567890"
    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '62') ? '0' . substr($digits, 2) : $digits;
    }

    // "081234567890" -> "0812 3456 7890"
    private function format(string $digits): string
    {
        return trim(preg_replace('/(\d{4})(?=\d)/', '$1 ', $digits));
    }
}