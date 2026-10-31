<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Kelola produk: daftar produk milik supplier yang login, lengkap dengan harga satuannya. */
    public function index()
    {
        $supplier = $this->currentSupplier();

        $products = RawMaterial::where('id_supplier', $supplier->id_supplier)
            ->orderBy('nama_bahan')
            ->get();

        return view('supplier.products.index', compact('supplier', 'products'));
    }

    /** Supplier mengubah harga satuan produknya sendiri. Owner tidak lagi mengatur harga ini. */
    public function update(Request $request, RawMaterial $material)
    {
        $supplier = $this->currentSupplier();

        // Supplier hanya boleh mengubah produknya sendiri
        abort_unless((int) $material->id_supplier === (int) $supplier->id_supplier, 404);

        $data = $request->validate([
            'harga_satuan' => ['required', 'integer', 'min:1', 'max:999999999'],
        ], [], ['harga_satuan' => 'unit price']);

        // updated_at pada bahan_baku menandai perubahan stok, jadi tidak ikut diperbarui oleh perubahan harga
        $material->timestamps = false;
        $material->update(['harga_satuan' => $data['harga_satuan']]);

        // Pesanan restock yang sudah dibuat tidak berubah: detail_restock menyimpan harga saat pesanan diajukan
        return redirect()
            ->route('supplier.products.index')
            ->with('status', 'Price for ' . $material->nama_bahan . ' is now Rp'
                . number_format($material->harga_satuan, 0, ',', '.') . ' per ' . $material->satuan . '.');
    }

    /** The supplier profile linked to the logged-in user (users.id_user -> supplier.id_user). */
    private function currentSupplier(): Supplier
    {
        $supplier = Supplier::where('id_user', auth()->id())->first();

        abort_unless($supplier, 403, 'Your account is not linked to a supplier profile yet. Please contact the manager.');

        return $supplier;
    }
}