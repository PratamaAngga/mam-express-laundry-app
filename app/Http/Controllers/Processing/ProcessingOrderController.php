<?php

namespace App\Http\Controllers\Processing;

use App\Exceptions\InvalidStatusTransition;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;

class ProcessingOrderController extends ProcessingController
{
    // Teks tombol sesuai status pesanan saat ini
    private const ACTION_LABELS = [
        Order::STATUS_QUEUED    => 'Start processing',
        Order::STATUS_PROCESSED => 'Mark as packed',
    ];

    // Kode diketik manual (cadangan kalau QR gagal terbaca)
    public function find(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $code = strtoupper(trim($data['code']));

        if (! Order::where('kode_pesanan', $code)->exists()) {
            return back()
                ->withErrors(['code' => 'Order code not found. Check the label and try again.'])
                ->withInput();
        }

        return redirect()->route('processing.order.show', ['code' => $code]);
    }

    // Halaman yang terbuka saat QR di label dipindai
    public function show(string $code)
    {
        $order = Order::with([
            'customer',
            'service',
            'additionalServices',
            'histories' => fn ($q) => $q->orderBy('waktu_perubahan')->orderBy('id_riwayat'),
        ])->where('kode_pesanan', strtoupper($code))->firstOrFail();

        return view('processing.order', $this->data() + [
            'order' => $order,
            'labels' => Order::STATUS_LABELS,
            'next' => OrderStatusService::next($order->status),
            'canAdvance' => OrderStatusService::allowed(auth()->user()->role, $order->status),
            'actionLabel' => self::ACTION_LABELS[$order->status] ?? null,
        ]);
    }

    // Tombol konfirmasi
    public function advance(Request $request, OrderStatusService $statuses, string $code)
    {
        $data = $request->validate(['from' => ['required', 'string']]);
        $code = strtoupper($code);

        try {
            $order = $statuses->advance($request->user(), $code, $data['from']);
        } catch (InvalidStatusTransition $e) {
            return redirect()->route('processing.order.show', ['code' => $code])->with('error', $e->getMessage());
        }

        return redirect()
            ->route('processing.order.show', ['code' => $code])
            ->with('status', 'Status updated to ' . Order::STATUS_LABELS[$order->status] . '.');
    }
}
