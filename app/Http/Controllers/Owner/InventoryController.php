<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RestockOrder;
use App\Models\StockLog;
use Illuminate\Http\Request;

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

        return view('owner.inventory.index', compact('materials', 'lowStock', 'summary', 'latestUsage'));
    }

    /**
     * Edit minimum stock (FR-12) dari pop-up di tabel Raw Material Inventory.
     * Hanya mengubah batas minimum; stok saat ini dan kolom "Last updated" tidak disentuh.
     */
    public function updateMinimum(Request $request, RawMaterial $material)
    {
        $data = $request->validate([
            'stok_minimum' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
        ], [], ['stok_minimum' => 'minimum stock']);

        // updated_at menunjukkan kapan stok terakhir berubah, jadi tidak ikut diperbarui oleh edit batas
        $material->timestamps = false;
        $material->update(['stok_minimum' => $data['stok_minimum']]);

        return redirect()
            ->route('owner.inventory.index')
            ->with('status', 'Minimum stock for ' . $material->nama_bahan . ' is now '
                . RawMaterial::formatNumber($material->stok_minimum) . ' ' . $material->satuan . '.');
    }
}