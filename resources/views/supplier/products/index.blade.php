@extends('layouts.supplier')

@section('title', 'Products')
@section('heading', 'Manage Products')
@section('subheading', 'Set the unit price of the products you supply. The laundry orders at these prices.')

@php
    $rupiah = fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
    <div class="bg-white border border-gray-200 rounded-2xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-[#eef3f2] text-gray-600">
                <tr>
                    <th class="px-6 py-4 font-medium">Product</th>
                    <th class="px-6 py-4 font-medium">Unit</th>
                    <th class="px-6 py-4 font-medium">Unit price</th>
                    <th class="px-6 py-4 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $p)
                    <tr>
                        <td class="px-6 py-5 font-medium">{{ $p->nama_bahan }}</td>
                        <td class="px-6 py-5">{{ $p->satuan }}</td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if ($p->harga_satuan > 0)
                                {{ $rupiah($p->harga_satuan) }} <span class="text-gray-500">/ {{ $p->satuan }}</span>
                            @else
                                <span class="inline-block whitespace-nowrap px-3 py-1 rounded-full text-xs bg-amber-50 text-amber-700">Price not set</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <button type="button"
                                class="js-edit-price inline-flex items-center gap-1.5 whitespace-nowrap border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold hover:bg-gray-50"
                                data-id="{{ $p->id_bahan }}"
                                data-action="{{ route('supplier.products.update', ['material' => $p->id_bahan]) }}"
                                data-name="{{ $p->nama_bahan }}"
                                data-unit="{{ $p->satuan }}"
                                data-price="{{ $p->harga_satuan > 0 ? (int) $p->harga_satuan : '' }}">
                                <i class="ti ti-edit"></i> Edit price
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">No products are linked to your account yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-sm text-gray-500 mt-4">
        A new price only applies to restock requests made after the change. Requests that were already submitted keep the price they were made with.
    </p>

    {{-- Pop-up Edit price --}}
    @php
        // Setelah validasi gagal, pop-up dibuka lagi untuk produk yang sama
        $editId = old('edit_product');
        $editing = $editId ? $products->firstWhere('id_bahan', (int) $editId) : null;
    @endphp
    <div id="priceModal" class="{{ $errors->has('harga_satuan') && $editing ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="priceModalTitle">
        <div class="absolute inset-0 bg-black/40" data-close-modal></div>
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-start justify-between gap-4 mb-1">
                <h2 id="priceModalTitle" class="text-xl font-semibold">Edit price</h2>
                <button type="button" class="text-gray-500 hover:text-gray-800" aria-label="Close" data-close-modal>
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-5">The laundry will see this price when ordering this product.</p>

            <form id="priceForm" method="POST" action="{{ $editing ? route('supplier.products.update', ['material' => $editing->id_bahan]) : '#' }}" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="edit_product" id="priceEditId" value="{{ $editing?->id_bahan }}">

                <div class="bg-[#eef3f2] rounded-xl px-4 py-3 mb-5 text-sm">
                    <p class="font-semibold" id="priceName">{{ $editing?->nama_bahan }}</p>
                    <p class="text-gray-500">Unit: <span class="price-unit">{{ $editing?->satuan }}</span></p>
                </div>

                <label for="harga_satuan" class="block text-sm font-medium mb-2">Unit price</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                    <input type="number" id="harga_satuan" name="harga_satuan" step="1" min="1" inputmode="numeric"
                        value="{{ old('harga_satuan') }}"
                        class="w-full border border-gray-200 rounded-lg pl-11 pr-20 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('harga_satuan') border-red-500 @enderror">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500">/ <span class="price-unit">{{ $editing?->satuan }}</span></span>
                </div>
                @error('harga_satuan')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" data-close-modal
                        class="border border-gray-200 rounded-lg px-5 py-3 text-sm font-semibold hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="bg-teal-700 hover:bg-teal-800 text-white rounded-lg px-5 py-3 text-sm font-semibold">Save price</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const modal = document.getElementById('priceModal');
        const form = document.getElementById('priceForm');
        const input = document.getElementById('harga_satuan');

        function openModal(btn) {
            form.action = btn.dataset.action;
            document.getElementById('priceEditId').value = btn.dataset.id;
            document.getElementById('priceName').textContent = btn.dataset.name;
            modal.querySelectorAll('.price-unit').forEach(el => el.textContent = btn.dataset.unit);
            input.value = btn.dataset.price;
            modal.classList.remove('hidden');
            input.focus();
            input.select();
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        document.querySelectorAll('.js-edit-price').forEach(btn => btn.addEventListener('click', () => openModal(btn)));
        modal.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', closeModal));
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    </script>
@endpush