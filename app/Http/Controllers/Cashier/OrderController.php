<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Services\OrderService;
use App\Services\QrCodeService;
use App\Services\WhatsApp\WhatsAppService;
use App\Exceptions\InvalidStatusTransition;
use App\Services\OrderStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // Daftar layanan aktif dari database (diatur owner di Service Pricing),
    // dibentuk sama seperti sebelumnya supaya view form tidak perlu diubah.
    private function serviceOptions(): array
    {
        return Service::active()
            ->orderBy('id_layanan')
            ->get()
            ->map(fn (Service $s) => [
                'code'  => (string) $s->id_layanan,
                'name'  => $s->nama_layanan,
                'price' => $s->harga_per_kg,
                'days'  => $s->estimasi_hari,
            ])
            ->all();
    }

    // Halaman New Order
    public function create(OrderService $orders)
    {
        $services = $this->serviceOptions();
        abort_if(empty($services), 503, 'Belum ada layanan aktif. Aktifkan layanan di menu Service Pricing (owner).');

        return view('cashier.orders.create', [
            'services' => $services,
            'freshenerPrice' => (int) $orders->freshener()->harga,
        ]);
    }

    // Tombol Save Order
    public function store(Request $request, OrderService $orders)
    {
        // Ubah "4,5" menjadi "4.5" sebelum divalidasi
        $request->merge(['weight' => str_replace(',', '.', (string) $request->input('weight'))]);

        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s\-()]{8,20}$/'],
            'customer_name' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'gt:0'],
            'service' => ['required', Rule::exists('jenis_layanan', 'id_layanan')->where('status_aktif', true)],
            'payment' => ['required', Rule::in(['cash_paid', 'transfer'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Semua yang menyangkut database & harga ada di OrderService
        $saved = $orders->create(auth()->user(), [
            'customer_name' => $data['customer_name'],
            'whatsapp' => $data['whatsapp'],
            'weight' => (float) $data['weight'],
            'service_id' => (int) $data['service'],
            'freshener' => $request->boolean('freshener'),
        ]);

        // Invoice WhatsApp lewat antrian; kegagalan di sini tidak boleh membatalkan pesanan yang sudah tersimpan
        try {
            app(WhatsAppService::class)->sendInvoice($saved);
        } catch (\Throwable $e) {
            report($e);
        }

        $service = $saved->service;
        $customer = $saved->customer;
        $addon = $saved->additionalServices->first();
        $freshenerTotal = $addon ? (float) $addon->pivot->harga_saat_ini : 0.0;

        $now = $saved->waktu_pesanan;
        $eta = $now->copy()->addDays($service->estimasi_hari);
        $kg = (float) $saved->berat_kg;

        // Metode bayar & catatan belum punya kolom di database, jadi hanya dibawa ke halaman sukses lewat session.
        $summary = [
            'code' => $saved->kode_pesanan,
            'cashier' => auth()->user()->nama,
            'customer_name' => $customer->nama,
            'whatsapp' => OrderService::formatPhone($customer->no_telepon),
            'service_name' => $service->nama_layanan,
            'price_per_kg' => $service->harga_per_kg,
            'weight_label' => rtrim(rtrim(number_format($kg, 2, ',', ''), '0'), ','),
            'service_total' => (float) $saved->total_harga - $freshenerTotal,
            'freshener' => (bool) $addon,
            'freshener_total' => $freshenerTotal,
            'total' => (float) $saved->total_harga,
            'payment_label' => $data['payment'] === 'transfer' ? 'Transfer' : 'Cash',
            'paid' => $saved->is_paid,
            'notes' => $data['notes'] ?? null,
            'posted_at' => $now->format('F j, Y') . ', ' . $this->time($now),
            'sent_at' => $now->format('j F Y') . ', ' . $now->format('H.i'),
            'eta_long' => $eta->format('F j, Y') . ', ' . $this->time($eta),
            'eta_label' => $eta->format('j M Y') . ', ' . $eta->format('H.i'),
            'eta_date_only' => $eta->format('F j'),
            'eta_time' => $this->time($eta),
        ];

        session(['last_order' => $summary]);

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

        // QR hanya berisi kode pesanan; pemindai di halaman Work Session yang mengurus sisanya
        $qr = app(QrCodeService::class)->rects($order['code']);

        return view('cashier.orders.success', ['order' => $order, 'qr' => $qr]);
    }

    // Halaman Order List (data dari database, 100 pesanan terbaru)
    public function index()
    {
        $orders = Order::with(['customer', 'service'])
            ->orderByDesc('waktu_pesanan')
            ->orderByDesc('id_pesanan')
            ->limit(100)
            ->get()
            ->map(fn (Order $o) => [
                'code' => $o->kode_pesanan,
                'date' => $o->waktu_pesanan->format('j M Y'),
                'time' => $o->waktu_pesanan->format('H.i'),
                'name' => $o->customer->nama,
                'phone' => OrderService::formatPhone($o->customer->no_telepon),
                'weight' => Order::kg($o->berat_kg),
                'service' => $o->service->nama_layanan,
                'status' => $o->status,
            ])
            ->all();

        $count = fn (array $keys) => count(array_filter($orders, fn ($o) => in_array($o['status'], $keys, true)));

        return view('cashier.orders.index', [
            'orders' => $orders,
            'statuses' => Order::STATUS_LABELS,
            'counts' => [
                'all' => count($orders),
                'queued' => $count([Order::STATUS_QUEUED]),
                'processing' => $count([Order::STATUS_PROCESSED]),
                'ready' => $count([Order::STATUS_PACKED]),
                'done' => $count([Order::STATUS_DONE]),
            ],
        ]);
    }

    // Order List -> View details page
    public function show(string $code)
    {
        $order = Order::with(['customer', 'service', 'cashier', 'additionalServices', 'histories.user'])
            ->where('kode_pesanan', $code)
            ->firstOrFail();

        $addon = $order->additionalServices->first();
        $addonTotal = $addon ? (float) $addon->pivot->harga_saat_ini : 0.0;
        $total = (float) $order->total_harga;
        $placed = $order->waktu_pesanan;
        $eta = $placed->copy()->addDays($order->service->estimasi_hari);
        $isDone = $order->status === Order::STATUS_DONE;

        // Timeline: one step per status. Older orders (from the seeder) may have no history rows,
        // so steps already passed are still marked as reached even if their time is unknown.
        $keys = array_keys(Order::STATUS_LABELS);
        $currentIndex = array_search($order->status, $keys, true);
        $histories = $order->histories->sortBy(fn ($h) => [$h->waktu_perubahan, $h->id_riwayat])->values();

        $timeline = [];
        foreach ($keys as $i => $key) {
            $entry = $histories->firstWhere('status_baru', $key);
            $when = $entry?->waktu_perubahan ?? ($i === 0 ? $placed : null);

            $timeline[] = [
                'label' => Order::STATUS_LABELS[$key],
                'reached' => $i <= $currentIndex,
                'current' => $i === $currentIndex,
                'time' => $when ? $when->format('j M Y, H.i') : null,
                'by' => $entry?->user?->nama,
            ];
        }

        $digits = preg_replace('/\D/', '', $order->customer->no_telepon);
        $waNumber = str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;

        return view('cashier.orders.show', [
            'order' => [
                'code' => $order->kode_pesanan,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'posted_at' => $placed->format('j M Y, H.i'),
                'cashier' => $order->cashier?->nama ?? '-',
                'customer_name' => $order->customer->nama,
                'phone' => OrderService::formatPhone($order->customer->no_telepon),
                'wa_url' => 'https://wa.me/' . $waNumber,
                'customer_orders' => $order->customer->orders()->count(),
                'service_name' => $order->service->nama_layanan,
                'price_per_kg' => Order::rupiah($order->service->harga_per_kg),
                'weight' => Order::kg($order->berat_kg),
                'service_total' => Order::rupiah($total - $addonTotal),
                'freshener' => (bool) $addon,
                'freshener_total' => Order::rupiah($addonTotal),
                'total' => Order::rupiah($total),
                'paid' => $order->is_paid,
                'eta' => $eta->format('j M Y, H.i'),
                'overdue' => ! $isDone && $eta->isPast(),
                'completed_at' => $order->waktu_selesai?->format('j M Y, H.i'),
                'can_complete' => OrderStatusService::allowed('kasir', $order->status),
                'blocked_reason' => OrderStatusService::blockedReason('kasir', $order->status),
            ],
            'timeline' => $timeline,
        ]);
    }

    // "Mark as picked up" button: dikemas_siap_diambil -> selesai (cashier only, via OrderStatusService)
    public function complete(string $code, OrderStatusService $statuses)
    {
        try {
            // Pass the status the cashier sees on screen, as a guard in case it has already changed.
            $statuses->advance(auth()->user(), $code, Order::STATUS_PACKED);
        } catch (InvalidStatusTransition $e) {
            return redirect()->route('cashier.orders.show', $code)->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('cashier.orders.show', $code)
            ->with('success', "Order {$code} marked as picked up.");
    }

    // 10:42 a.m. / 3:05 p.m.
    private function time(Carbon $date): string
    {
        return $date->format('g:i') . ' ' . ($date->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }
}