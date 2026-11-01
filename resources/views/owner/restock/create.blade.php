@extends('layouts.owner')

@section('title', 'Restock Supplier')
@section('heading', 'Ajukan Restock')
@section('subheading', 'Choose a supplier and the materials you need.')

@section('content')
    <form method="POST" action="{{ route('owner.restock.store') }}"
        class="max-w-4xl bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
        @csrf

        <div>
            <label for="id_supplier" class="block text-sm text-gray-700 mb-1">Supplier</label>
            <select name="id_supplier" id="id_supplier"
                class="w-full md:w-1/2 border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('id_supplier') border-red-500 @enderror">
                <option value="">Select a supplier</option>
                @foreach ($catalog as $s)
                    <option value="{{ $s['id'] }}" @selected(old('id_supplier') == $s['id'])>{{ $s['name'] }}</option>
                @endforeach
            </select>
            @error('id_supplier')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="font-semibold">Materials</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Unit prices are set by the supplier and cannot be changed here.</p>
                </div>
                <button type="button" id="addRow" class="text-sm text-teal-700 font-semibold hover:underline">
                    <i class="ti ti-plus"></i> Add material
                </button>
            </div>

            <div id="rows" class="space-y-3"></div>

            @error('items')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
            @error('items.*.id_bahan')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
            @error('items.*.jumlah')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror

            <div class="flex justify-between border-t border-gray-200 mt-5 pt-4 font-semibold">
                <span>Total applications</span>
                <span id="total">Rp0</span>
            </div>
        </div>

        <div>
            <label for="catatan" class="block text-sm text-gray-700 mb-1">Note to the supplier (optional)</label>
            <textarea name="catatan" id="catatan" rows="3" maxlength="255"
                placeholder="Stock is running low, please make this a priority."
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">{{ old('catatan') }}</textarea>
            @error('catatan')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-6 py-3">
                Submit Request
            </button>
            <a href="{{ route('owner.restock.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const catalog = @json($catalog);
        const oldItems = @json(old('items', []));

        const supplierSelect = document.getElementById('id_supplier');
        const rowsEl = document.getElementById('rows');
        const totalEl = document.getElementById('total');
        let counter = 0;

        const rupiah = n => 'Rp' + Math.round(n).toLocaleString('id-ID');

        function materialsOf(supplierId) {
            const s = catalog.find(c => String(c.id) === String(supplierId));
            return s ? s.materials : [];
        }

        function fillOptions(select, selected = '') {
            const materials = materialsOf(supplierSelect.value);
            select.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = supplierSelect.value ? 'Select material' : 'Select a supplier first';
            select.appendChild(placeholder);

            materials.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                const hasPrice = m.price > 0;
                opt.textContent = m.name + ' (' + m.unit + ')' + (hasPrice ? '' : ' - price not set');
                opt.dataset.price = m.price;
                opt.disabled = !hasPrice;
                if (String(selected) === String(m.id)) opt.selected = true;
                select.appendChild(opt);
            });
        }

        function recalc() {
            let total = 0;
            rowsEl.querySelectorAll('.row-item').forEach(row => {
                const qty = parseFloat(row.querySelector('.qty').value) || 0;
                const selected = row.querySelector('.mat').selectedOptions[0];
                const price = selected ? parseFloat(selected.dataset.price) || 0 : 0;
                row.querySelector('.price').textContent = price > 0 ? rupiah(price) : '-';
                const sub = qty * price;
                row.querySelector('.sub').textContent = rupiah(sub);
                total += sub;
            });
            totalEl.textContent = rupiah(total);
        }

        function addRow(data = {}) {
            const i = counter++;
            const row = document.createElement('div');
            row.className = 'row-item grid grid-cols-12 gap-3 items-end';
            row.innerHTML = `
            <div class="col-span-12 md:col-span-5">
                <label class="block text-xs text-gray-500 mb-1">Material</label>
                <select name="items[${i}][id_bahan]" class="mat w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600"></select>
            </div>
            <div class="col-span-4 md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                <input type="number" step="0.01" min="0.01" name="items[${i}][jumlah]" value="${data.jumlah ?? ''}"
                    class="qty w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
            </div>
            <div class="col-span-5 md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Unit price</label>
                <div class="price text-sm text-gray-700 pb-2.5 whitespace-nowrap">-</div>
            </div>
            <div class="col-span-3 md:col-span-2 text-sm font-medium pb-2.5 sub">Rp0</div>
            <div class="col-span-12 md:col-span-1 pb-1.5 text-right">
                <button type="button" class="remove text-gray-400 hover:text-red-600 text-xl" title="Remove"><i class="ti ti-trash"></i></button>
            </div>`;

            rowsEl.appendChild(row);
            fillOptions(row.querySelector('.mat'), data.id_bahan ?? '');

            row.querySelector('.qty').addEventListener('input', recalc);
            row.querySelector('.mat').addEventListener('change', recalc);
            row.querySelector('.remove').addEventListener('click', () => {
                if (rowsEl.children.length > 1) {
                    row.remove();
                    recalc();
                }
            });
            recalc();
        }

        supplierSelect.addEventListener('change', () => {
            rowsEl.querySelectorAll('.mat').forEach(sel => fillOptions(sel));
            recalc();
        });
        document.getElementById('addRow').addEventListener('click', () => addRow());

        const initial = Object.values(oldItems);
        if (initial.length) {
            initial.forEach(item => addRow(item));
        } else {
            addRow();
        }
    </script>
@endpush