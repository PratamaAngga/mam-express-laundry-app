<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\RestockOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    /**
     * Shipping update form. Editable while the order is Confirmed,
     * read-only once it is Sent or Accepted.
     */
    public function edit(RestockOrder $order)
    {
        $supplier = $this->currentSupplier();
        $this->authorizeOrder($order, $supplier);

        if ($order->status === 'diajukan') {
            return redirect()->route('supplier.restock.show', $order)
                ->with('error', 'Confirm the order first, then set up the shipment.');
        }

        $order->load(['details.material', 'owner']);

        return view('supplier.shipments.edit', [
            'supplier' => $supplier,
            'order'    => $order,
            'carriers' => $this->carriers($supplier),
            'readonly' => $order->status !== 'dikonfirmasi',
        ]);
    }

    /**
     * Shipments list: orders that are confirmed, on the way, or delivered.
     */
    public function index(Request $request)
    {
        $supplier = $this->currentSupplier();

        $status = $request->query('status');
        $tabs   = ['dikonfirmasi', 'dikirim', 'diterima'];

        $base = RestockOrder::where('id_supplier', $supplier->id_supplier)
            ->whereIn('status', $tabs);

        $counts = collect($tabs)
            ->mapWithKeys(fn ($s) => [$s => (clone $base)->where('status', $s)->count()])
            ->all();

        $orders = (clone $base)
            ->with('details.material')
            ->when(in_array($status, $tabs, true), fn ($query) => $query->where('status', $status))
            // Ready to ship first, then on the way, then delivered
            ->orderByRaw("CASE status WHEN 'dikonfirmasi' THEN 1 WHEN 'dikirim' THEN 2 ELSE 3 END")
            ->orderByDesc('tanggal_pengajuan')
            ->paginate(10)
            ->withQueryString();

        return view('supplier.shipments.index', compact('supplier', 'orders', 'counts', 'status'));
    }

    /** Confirmed -> Sent. "Accepted" is recorded by the laundry side, not the supplier. */
    public function update(Request $request, RestockOrder $order)
    {
        $supplier = $this->currentSupplier();
        $this->authorizeOrder($order, $supplier);

        if ($order->status !== 'dikonfirmasi') {
            return redirect()->route('supplier.shipments.edit', $order)
                ->with('error', 'This order is no longer waiting for shipment. Reload the page and try again.');
        }

        // The shipping time cannot be earlier than the confirmation time
        $shippedRules = ['required', 'date'];
        if ($order->tanggal_dikonfirmasi) {
            $shippedRules[] = 'after_or_equal:' . $order->tanggal_dikonfirmasi->format('Y-m-d H:i');
        }

        $data = $request->validate([
            'carrier'           => ['required', Rule::in($this->carriers($supplier))],
            'courier_name'      => ['required', 'string', 'max:100'],
            'courier_phone'     => ['required', 'string', 'max:20'],
            'tracking_number'   => ['required', 'string', 'max:50'],
            'shipped_at'        => $shippedRules,
            'estimated_arrival' => ['required', 'date', 'after_or_equal:shipped_at'],
            'shipping_note'     => ['nullable', 'string', 'max:255'],
            'handed_over'       => ['accepted'],
        ], [
            'handed_over.accepted'             => 'Please confirm that the items have been handed over to the courier.',
            'shipped_at.after_or_equal'        => 'The shipping time cannot be earlier than the order confirmation.',
            'estimated_arrival.after_or_equal' => 'The estimated arrival cannot be earlier than the shipping time.',
        ]);

        DB::transaction(function () use ($order, $data) {
            $order->update([
                'status'             => 'dikirim',
                'tanggal_dikirim'    => $data['shipped_at'],
                'ekspedisi'          => $data['carrier'],
                'nama_kurir'         => $data['courier_name'],
                'kontak_kurir'       => $data['courier_phone'],
                'nomor_resi'         => $data['tracking_number'],
                'estimasi_tiba'      => $data['estimated_arrival'],
                'catatan_pengiriman' => $data['shipping_note'] ?? null,
            ]);

            $order->details()->update(['status' => 'dikirim']);
        });

        // TODO (backend): notify the owner via WhatsApp / notifikasi_wa

        return redirect()->route('supplier.shipments.edit', $order)
            ->with('status', 'Order ' . $order->kode_restock . ' has been marked as shipped.');
    }

    /** Courier options. The first one is the supplier's own courier. */
    private function carriers(Supplier $supplier): array
    {
        return [
            'Internal courier of ' . $supplier->nama_supplier,
            'JNE',
            'J&T Express',
            'SiCepat',
            'Other courier',
        ];
    }

    /** A supplier can only open and change orders addressed to them. */
    private function authorizeOrder(RestockOrder $order, Supplier $supplier): void
    {
        abort_unless($order->id_supplier === $supplier->id_supplier, 404);
    }

    /** The supplier profile linked to the logged-in user (users.id_user -> supplier.id_user). */
    private function currentSupplier(): Supplier
    {
        $supplier = Supplier::where('id_user', auth()->id())->first();

        abort_unless($supplier, 403, 'Your account is not linked to a supplier profile yet. Please contact the manager.');

        return $supplier;
    }
}