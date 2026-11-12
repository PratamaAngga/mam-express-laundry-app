<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Models\RawMaterial;
use App\Models\RestockOrder;
use App\Models\StockLog;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    /**
     * Raw Material Inventory — FR-11 (real-time stock), FR-12 (minimum level).
     */
    public function index()
    {
        // Low-stock items first, then most recently updated
        $materials = RawMaterial::with('supplier')
            ->orderByRaw('(stok_saat_ini <= stok_minimum) DESC')
            ->orderByDesc('updated_at')
            ->get();

        $lowStock = $materials->filter(fn ($m) => $m->is_low)->values();

        $summary = [
            'total'           => $materials->count(),
            'sufficient'      => $materials->count() - $lowStock->count(),
            'low'             => $lowStock->count(),
            // Restock requests waiting for supplier confirmation
            'active_requests' => RestockOrder::where('status', 'diajukan')->count(),
        ];

        $latestUsage = StockLog::with('material')
            ->where('tipe', 'keluar')
            ->latest('waktu')
            ->limit(5)
            ->get();

        // Layanan tambahan (mis. pewangi) yang bisa menjadi pemicu pemakaian bahan
        $addons = AdditionalService::orderBy('nama_tambahan')->get();

        return view('owner.inventory.index', compact('materials', 'lowStock', 'summary', 'latestUsage', 'addons'));
    }

    /**
     * Edit minimum stock (FR-12) dari pop-up di tabel Raw Material Inventory.
     * Hanya mengubah batas minimum; stok saat ini dan kolom "Last updated" tidak disentuh.
     */
    public function updateMinimum(Request $request, RawMaterial $material)
    {
        $data = $request->validate([
            'stok_minimum' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
            'usage_per_kg' => ['nullable', 'numeric', 'min:0', 'max:1000000', 'decimal:0,3'],
            'id_tambahan_pemicu' => ['nullable', 'integer', Rule::exists('layanan_tambahan', 'id_tambahan')],
        ], [], [
            'stok_minimum' => 'minimum stock',
            'usage_per_kg' => 'usage per kg',
            'id_tambahan_pemicu' => 'add-on',
        ]);

        $update = ['stok_minimum' => $data['stok_minimum']];

        // Laju pemakaian hanya diubah kalau formulir mengirimkannya. Kosong atau 0 = tidak dipotong otomatis
        if ($request->has('usage_per_kg')) {
            $usage = $data['usage_per_kg'] ?? null;
            $rate = ($usage === null || (float) $usage <= 0) ? null : StockService::toStoredRate((float) $usage, $material->satuan);

            $update['pemakaian_per_kg'] = $rate;
            // Pemicu layanan tambahan hanya berarti kalau ada laju pemakaian
            $update['id_tambahan_pemicu'] = $rate === null ? null : ($data['id_tambahan_pemicu'] ?? null);
        }

        // updated_at menunjukkan kapan stok terakhir berubah, jadi tidak ikut diperbarui oleh edit pengaturan
        $material->timestamps = false;
        $material->update($update);

        return redirect()
            ->route('owner.inventory.index')
            ->with('status', 'Settings for ' . $material->nama_bahan . ' saved. Minimum stock is now '
                . RawMaterial::formatNumber($material->stok_minimum) . ' ' . $material->satuan . '.');
    }
}