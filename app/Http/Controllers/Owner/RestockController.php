<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RestockDetail;
use App\Models\RestockOrder;
use App\Models\Supplier;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestockController extends Controller
{
    /**
     * Restock Supplier: order list + detail panel (FR-13).
     */
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $orders = RestockOrder::with(['supplier', 'owner', 'details.material'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('kode_restock', 'like', "%{$q}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('nama_supplier', 'like', "%{$q}%"))))
            ->when(in_array($status, RestockOrder::FLOW, true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal_pengajuan')
            ->get();

        // Selected order for the detail panel (default: newest)
        $selected = $orders->firstWhere('kode_restock', $request->query('order')) ?? $orders->first();

        $suppliers = Supplier::orderBy('nama_supplier')->get();

        return view('owner.restock.index', compact('orders', 'selected', 'suppliers', 'q', 'status'));
    }

    public function create()
    {
        // Suppliers with their materials, used by the form to filter the material dropdown
        $catalog = Supplier::with('rawMaterials')
            ->orderBy('nama_supplier')
            ->get()
            ->map(fn ($s) => [
                'id'        => $s->id_supplier,
                'name'      => $s->nama_supplier,
                'materials' => $s->rawMaterials->map(fn ($m) => [
                    'id'   => $m->id_bahan,
                    'name' => $m->nama_bahan,
                    'unit' => $m->satuan,
                ])->values(),
            ])
            ->values();

        return view('owner.restock.create', compact('catalog'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_supplier'          => ['required', 'exists:supplier,id_supplier'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.id_bahan'     => ['required', 'exists:bahan_baku,id_bahan'],
            'items.*.jumlah'       => ['required', 'numeric', 'min:0.01'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'catatan'              => ['nullable', 'string', 'max:255'],
        ]);

        // Every material must belong to the chosen supplier
        $allowed = RawMaterial::where('id_supplier', $data['id_supplier'])->pluck('id_bahan')->all();

        foreach ($data['items'] as $item) {
            if (! in_array((int) $item['id_bahan'], $allowed, true)) {
                throw ValidationException::withMessages([
                    'items' => 'All materials must come from the selected supplier.',
                ]);
            }
        }

        $order = DB::transaction(function () use ($data) {
            $sequence = (RestockOrder::max('id_restock') ?? 0) + 1;

            $order = RestockOrder::create([
                'kode_restock'      => 'RST-' . now()->format('ymd') . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT),
                'id_supplier'       => $data['id_supplier'],
                'id_user_owner'     => auth()->id(),
                'status'            => 'diajukan',
                'catatan'           => $data['catatan'] ?? null,
                'tanggal_pengajuan' => now(),
            ]);

            foreach ($data['items'] as $item) {
                RestockDetail::create([
                    'id_restock'   => $order->id_restock,
                    'id_bahan'     => $item['id_bahan'],
                    'jumlah'       => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'status'       => 'diajukan',
                ]);
            }

            return $order;
        });

        // Permintaan restock dikirim ke WhatsApp supplier (FR-14) lewat antrian; kegagalan tidak membatalkan restock
        try {
            app(WhatsAppService::class)->sendRestockRequest($order->load(['supplier', 'details.material']));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('owner.restock.index', ['order' => $order->kode_restock])
            ->with('status', 'Restock request ' . $order->kode_restock . ' has been submitted to the supplier.');
    }
}