<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Order;
use App\Models\Service;
use App\Models\WhatsappMessage;
use App\Services\OrderService;
use App\Services\OrderStatusService;
use App\Services\QrCodeService;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    // Halaman View details dari Order List: semua yang perlu kasir untuk menjawab pertanyaan pelanggan
    public function show(string $code)
    {
        $order = Order::with(['customer', 'service', 'cashier', 'additionalServices', 'histories.user', 'whatsappMessages'])
            ->where('kode_pesanan', strtoupper($code))
            ->firstOrFail();

        $addon = $order->additionalServices->first();
        $addonTotal = $addon ? (float) $addon->pivot->harga_saat_ini : 0.0;
        $total = (float) $order->total_harga;
        $placed = $order->waktu_pesanan;
        $eta = $placed->copy()->addDays($order->service->estimasi_hari);
        $isDone = $order->status === Order::STATUS_DONE;

        // Linimasa: satu langkah per status. Pesanan lama (dari seeder) bisa tanpa baris riwayat,
        // jadi langkah yang sudah lewat tetap ditandai tercapai walau waktunya tidak diketahui.
        $keys = array_keys(Order::STATUS_LABELS);
        $currentIndex = array_search($order->status, $keys, true);
        $histories = $order->histories->sortBy(fn ($h) => [$h->waktu_perubahan, $h->id_riwayat])->values();

        $timeline = [];
        foreach ($keys as $i => $key) {
            $entry = $histories->firstWhere('status_baru', $key);
            $when = $entry?->waktu_perubahan;
            $by = $entry?->user?->nama;

            // Langkah pertama = pesanan dibuat; langkah terakhir bisa jatuh kembali ke waktu_selesai
            if ($i === 0) {
                $when ??= $placed;
                $by ??= $order->cashier?->nama;
            } elseif ($key === Order::STATUS_DONE) {
                $when ??= $order->waktu_selesai;
            }

            $timeline[] = [
                'label' => Order::STATUS_LABELS[$key],
                'reached' => $i <= $currentIndex,
                'current' => $i === $currentIndex,
                'time' => $when?->format('j M Y, H.i'),
                'by' => $by,
            ];
        }

        // Riwayat pesan WhatsApp ke pelanggan (invoice dan kabar status), terbaru di atas
        $messages = $order->whatsappMessages
            ->whereIn('tipe', [WhatsappMessage::TIPE_INVOICE_PELANGGAN, WhatsappMessage::TIPE_STATUS_PELANGGAN])
            ->sortByDesc('id_notifikasi')
            ->map(fn (WhatsappMessage $m) => [
                'kind' => $m->tipe === WhatsappMessage::TIPE_INVOICE_PELANGGAN ? 'Invoice' : 'Status update',
                'state' => $m->status_kirim,
                'state_label' => match ($m->status_kirim) {
                    WhatsappMessage::TERKIRIM => 'Sent',
                    WhatsappMessage::GAGAL => 'Failed',
                    WhatsappMessage::SIMULASI => 'Simulated',
                    default => 'Queued',
                },
                'time' => $m->waktu_kirim?->format('j M Y, H.i'),
                'text' => Str::limit($m->isi_pesan, 160),
                'reason' => $m->status_kirim === WhatsappMessage::GAGAL ? $m->alasan_gagal : null,
            ])
            ->values()
            ->all();

        $waNumber = '62' . substr(OrderService::normalizePhone($order->customer->no_telepon), 1);

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
            'messages' => $messages,
        ]);
    }

    // Tombol "Mark as picked up" di Order List: pesanan Dikemas dan Siap Diambil menjadi Selesai.
    // Aturan (hanya kasir, hanya dari status siap diambil, tercatat di riwayat) ada di OrderStatusService.
    public function complete(Request $request, OrderStatusService $statuses, string $code)
    {
        $data = $request->validate(['from' => ['required', 'string']]);

        try {
            $order = $statuses->advance($request->user(), strtoupper($code), $data['from']);
        } catch (ModelNotFoundException $e) {
            return back()->with('error', 'Order not found.');
        } catch (InvalidStatusTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Order {$order->kode_pesanan} marked as picked up.");
    }

    // 10:42 a.m. / 3:05 p.m.
    private function time(Carbon $date): string
    {
        return $date->format('g:i') . ' ' . ($date->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }
}