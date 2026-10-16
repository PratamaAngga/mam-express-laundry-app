<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Arahkan user ke dashboard sesuai role (dipakai oleh redirect()->intended('/dashboard')).
     */
    public function index(Request $request)
    {
        return match ($request->user()->role) {
            'owner'              => redirect()->route('owner.dashboard'),
            'kasir'              => redirect()->route('cashier.orders.create'),
            // 'petugas_processing' => redirect()->route('processing.orders.index'),
            // 'supplier'           => redirect()->route('supplier.orders.index'),
            default              => abort(403),
        };
    }
}
