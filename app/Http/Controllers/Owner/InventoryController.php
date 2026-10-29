<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RestockOrder;
use App\Models\StockLog;

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
}