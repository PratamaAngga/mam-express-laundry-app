<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RestockDetail;
use App\Models\StockLog;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // Satuan yang boleh dipilih supplier (seragam, supaya stok dan laporan tidak bercampur satuan)
    private const UNITS = ['liters', 'kg', 'pcs', 'sheets', 'packs'];

    /** Kelola produk: daftar produk milik supplier yang login, lengkap dengan harga satuannya. */
    public function index()
    {
        $supplier = $this->currentSupplier();

        $products = RawMaterial::where('id_supplier', $supplier->id_supplier)
            ->orderBy('nama_bahan')
            ->get();

        $units = self::UNITS;

        return view('supplier.products.index', compact('supplier', 'products', 'units'));
    }

    /**
     * Supplier menambah produk baru. Produk baru otomatis milik supplier ini dan mulai dengan stok 0;
     * owner yang mengatur stok minimum dan laju pemakaiannya di halaman Inventory.
     */
    public function store(Request $request)
    {
        $supplier = $this->currentSupplier();

        $data = $request->validate([
            'nama_bahan' => [
                'required', 'string', 'max:100',
                Rule::unique('bahan_baku', 'nama_bahan')->where('id_supplier', $supplier->id_supplier),
            ],
            'satuan' => ['required', Rule::in(self::UNITS)],
            'harga_satuan' => ['required', 'integer', 'min:1', 'max:999999999'],
        ], [
            'nama_bahan.unique' => 'You already have a product with this name.',
        ], ['nama_bahan' => 'product name', 'satuan' => 'unit', 'harga_satuan' => 'unit price']);

        $material = RawMaterial::create([
            'nama_bahan'    => $data['nama_bahan'],
            'satuan'        => $data['satuan'],
            'stok_saat_ini' => 0,
            'stok_minimum'  => 0,
            'id_supplier'   => $supplier->id_supplier,
            'harga_satuan'  => $data['harga_satuan'],
        ]);

        return redirect()
            ->route('supplier.products.index')
            ->with('status', $material->nama_bahan . ' was added at Rp'
                . number_format($material->harga_satuan, 0, ',', '.') . ' per ' . $material->satuan . '.');
    }

    /** Menghapus produk yang belum pernah dipakai. Produk dengan riwayat restock atau stok tidak boleh dihapus. */
    public function destroy(RawMaterial $material)
    {
        $supplier = $this->currentSupplier();

        // Supplier hanya boleh menghapus produknya sendiri
        abort_unless((int) $material->id_supplier === (int) $supplier->id_supplier, 404);

        $inUse = RestockDetail::where('id_bahan', $material->id_bahan)->exists()
            || StockLog::where('id_bahan', $material->id_bahan)->exists()
            || (float) $material->stok_saat_ini > 0;

        if ($inUse) {
            return redirect()
                ->route('supplier.products.index')
                ->with('error', $material->nama_bahan . ' has restock or stock history, so it cannot be deleted.');
        }

        try {
            $material->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('supplier.products.index')
                ->with('error', $material->nama_bahan . ' is still linked to other records and cannot be deleted.');
        }

        return redirect()
            ->route('supplier.products.index')
            ->with('status', $material->nama_bahan . ' was deleted.');
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