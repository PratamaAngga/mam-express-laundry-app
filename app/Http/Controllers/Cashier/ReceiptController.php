<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    // Halaman Print Receipt (data dari database, 50 pesanan terbaru)
    public function index(Request $request)
    {
        $orders = Order::with(['customer', 'service', 'additionalServices'])
            ->orderByDesc('waktu_pesanan')
            ->orderByDesc('id_pesanan')
            ->limit(50)
            ->get()
            ->map(fn (Order $o) => $this->present($o))
            ->all();

        // Belum ada pesanan sama sekali: kembali ke form pesanan baru
        if (empty($orders)) {
            return redirect()->route('cashier.orders.create');
        }

        $selected = collect($orders)->firstWhere('code', $request->query('order')) ?? $orders[0];

        return view('cashier.receipt.index', [
            'orders' => $orders,
            'selected' => $selected,
        ]);
    }

    // Bentuk data satu pesanan untuk halaman struk (kunci array sama seperti sebelumnya).
    private function present(Order $o): array
    {
        $kg = (float) $o->berat_kg;
        $addon = $o->additionalServices->first();
        $freshenerTotal = $addon ? (int) round($addon->pivot->harga_saat_ini) : 0;
        $total = (int) round($o->total_harga);
        $serviceTotal = $total - $freshenerTotal;
        $paid = $o->is_paid;
        $paidAmount = $paid ? $total : 0;
        $phone = OrderService::formatPhone($o->customer->no_telepon);
        $placed = $o->waktu_pesanan;
        $eta = $placed->copy()->addDays($o->service->estimasi_hari);
        $kgLabel = Order::kg($kg);
        $serviceName = $o->service->nama_layanan;
        // QR berisi alamat halaman order untuk petugas processing (dibuka lewat kamera HP)
        $qr = app(QrCodeService::class)->rects(route('processing.order.show', ['code' => $o->kode_pesanan]));

        return [
            'code' => $o->kode_pesanan,
            'option_label' => $o->kode_pesanan . ' • ' . $o->customer->nama,
            'name' => $o->customer->nama,
            'phone' => $phone,
            'date_short' => $placed->format('j M Y') . ', ' . $placed->format('H.i'),
            'received' => 'Received ' . $placed->format('F j, Y') . ', ' . $this->time($placed),
            'service_name' => $serviceName,
            'calc' => $kgLabel . ' kg × ' . $this->rp((int) round($o->service->harga_per_kg)) . '/kg',
            'service_total' => $this->rp($serviceTotal),
            'freshener' => (bool) $addon,
            'freshener_total' => $this->rp($freshenerTotal),
            'total' => $this->rp($total),
            'paid' => $paid,
            'paid_label' => $paid ? 'paid off' : 'unpaid',
            'paid_amount' => $this->rp($paidAmount),
            'remaining' => $this->rp($total - $paidAmount),
            'eta' => $eta->format('F j, Y') . ', ' . $this->time($eta),
            'status_key' => $o->status,
            'status_label' => Order::STATUS_LABELS[$o->status] ?? $o->status,
            'weight_service' => $kgLabel . ' kg • ' . $serviceName,
            'completed' => 'Completed: ' . $eta->format('M j, Y') . ', ' . $this->time($eta),
            'sent_text' => 'To ' . $phone . ' on ' . $placed->format('F j') . ' at ' . $this->time($placed) . '.',
            'qr' => $qr['rects'],
            'qr_size' => $qr['size'],
        ];
    }

    private function rp(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    // 10:42 a.m. / 3:05 p.m.
    private function time(Carbon $date): string
    {
        return $date->format('g:i') . ' ' . ($date->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }
}
