<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\RestockOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RestockOrderController extends Controller
{
    private const PER_PAGE = 10;

    /**
     * Supplier: restock orders addressed to the logged-in supplier only.
     */
    public function index(Request $request)
    {
        $supplier = $this->currentSupplier();

        $q      = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $period = $request->query('period', 'all');

        $base = RestockOrder::where('id_supplier', $supplier->id_supplier);

        // Counts ignore the filters
        $counts = collect(RestockOrder::FLOW)
            ->mapWithKeys(fn ($s) => [$s => (clone $base)->where('status', $s)->count()])
            ->all();

        $orders = (clone $base)
            ->with(['details.material'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('kode_restock', 'like', "%{$q}%")
                ->orWhereHas('details.material', fn ($m) => $m->where('nama_bahan', 'like', "%{$q}%"))))
            ->when(in_array($status, RestockOrder::FLOW, true), fn ($query) => $query->where('status', $status))
            ->when($period === '30', fn ($query) => $query->where('tanggal_pengajuan', '>=', now()->subDays(30)->startOfDay()))
            ->when($period === '90', fn ($query) => $query->where('tanggal_pengajuan', '>=', now()->subDays(90)->startOfDay()))
            // Orders that need action come first, then newest first
            ->orderByRaw("CASE status WHEN 'diajukan' THEN 1 WHEN 'dikonfirmasi' THEN 2 WHEN 'dikirim' THEN 3 ELSE 4 END")
            ->orderByDesc('tanggal_pengajuan')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $manager = User::where('role', 'owner_admin')->orderBy('id_user')->first();

        return view('supplier.restock.index', compact('supplier', 'orders', 'counts', 'manager', 'q', 'status', 'period'));
    }

    /** Order detail + confirmation form. */
    public function show(RestockOrder $order)
    {
        $supplier = $this->currentSupplier();
        $this->authorizeOrder($order, $supplier);

        $order->load(['details.material', 'owner']);

        return view('supplier.restock.show', compact('supplier', 'order'));
    }

    /** Submitted -> Confirmed */
    public function confirm(Request $request, RestockOrder $order)
    {
        $this->authorizeOrder($order, $this->currentSupplier());

        $data = $request->validate([
            'estimated_delivery' => ['required', 'date', 'after:now'],
            'confirmation_note'  => ['nullable', 'string', 'max:255'],
            'agree'              => ['accepted'],
        ], [
            'agree.accepted'            => 'Please confirm that the quantity and price are correct.',
            'estimated_delivery.after'  => 'The estimated delivery cannot be in the past.',
        ]);

        return $this->advance($order, 'diajukan', 'dikonfirmasi', 'tanggal_dikonfirmasi', [
            'perkiraan_kirim'    => Carbon::parse($data['estimated_delivery']),
            'catatan_konfirmasi' => $data['confirmation_note'] ?? null,
        ], 'Order ' . $order->kode_restock . ' has been confirmed.');
    }

    /** Confirmed -> Sent. "Accepted" is recorded by the laundry side, not the supplier. */
    public function ship(RestockOrder $order)
    {
        $this->authorizeOrder($order, $this->currentSupplier());

        return $this->advance($order, 'dikonfirmasi', 'dikirim', 'tanggal_dikirim', [],
            'Order ' . $order->kode_restock . ' has been marked as shipped.');
    }

    private function advance(RestockOrder $order, string $from, string $to, string $dateColumn, array $extra, string $message)
    {
        if ($order->status !== $from) {
            return redirect()->route('supplier.restock.show', $order)
                ->with('error', 'The order status has changed. Reload the page and try again.');
        }

        DB::transaction(function () use ($order, $to, $dateColumn, $extra) {
            $order->update(array_merge(['status' => $to, $dateColumn => now()], $extra));
            $order->details()->update(['status' => $to]);
        });

        // TODO (backend): notify the owner via WhatsApp / notifikasi_wa

        return redirect()->route('supplier.restock.show', $order)->with('status', $message);
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
