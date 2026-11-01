@extends('layouts.owner')

@section('title', 'Raw Material Inventory')
@section('heading', 'Raw Material Inventory')
@section('subheading', 'Monitor material availability to ensure operations continue without interruption.')

@php
    use App\Models\RawMaterial;
    use Illuminate\Support\Facades\Route;

    $restockUrl = Route::has('owner.restock.create') ? route('owner.restock.create') : '#';
@endphp

@section('actions')
    <a href="#"
        class="inline-flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-5 py-3 text-sm font-semibold hover:bg-gray-50">
        <i class="ti ti-minus"></i> Track Usage
    </a>
@endsection

@section('content')
    {{-- Warning banner --}}
    @if ($lowStock->isNotEmpty())
        <div class="mb-6 bg-amber-50 rounded-xl px-6 py-4 flex items-start gap-4">
            <i class="ti ti-alert-triangle text-2xl text-amber-700 mt-0.5"></i>
            <div>
                <p class="font-semibold text-amber-800">{{ $lowStock->count() }}
                    {{ $lowStock->count() > 1 ? 'items are' : 'item is' }} below the minimum stock level</p>
                <p class="text-sm text-amber-800">
                    {{ $lowStock->pluck('nama_bahan')->map(fn($n) => ucfirst($n))->join(' and ') }}
                    {{ $lowStock->count() > 1 ? 'are' : 'is' }} running low. Please request a restock from the supplier
                    immediately.
                </p>
            </div>
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @php
            $cards = [
                [
                    'label' => 'Listed ingredients',
                    'value' => $summary['total'],
                    'note' => 'All operational materials',
                    'icon' => 'ti-package',
                    'tone' => 'text-teal-700',
                ],
                [
                    'label' => 'Sufficient stock',
                    'value' => $summary['sufficient'],
                    'note' => 'Above the minimum threshold',
                    'icon' => 'ti-circle-check',
                    'tone' => 'text-teal-700',
                ],
                [
                    'label' => 'Ingredients are running low',
                    'value' => $summary['low'],
                    'note' => 'A restock request needs to be submitted',
                    'icon' => 'ti-alert-triangle',
                    'tone' => 'text-amber-600',
                ],
                [
                    'label' => 'Active requests',
                    'value' => $summary['active_requests'],
                    'note' => 'Waiting for confirmation from the supplier',
                    'icon' => 'ti-truck-delivery',
                    'tone' => 'text-teal-700',
                ],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>{{ $c['label'] }}</span>
                    <i class="ti {{ $c['icon'] }} text-xl {{ $c['tone'] }}"></i>
                </div>
                <p class="text-4xl font-bold my-3">{{ $c['value'] }}</p>
                <p class="text-sm text-gray-500">{{ $c['note'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Search & filter --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="relative w-full max-w-md">
            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            <input id="search" type="text" placeholder="Search for an ingredient name"
                class="w-full bg-white border border-gray-200 rounded-lg pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
        </div>
        <div class="relative">
            <select id="statusFilter"
                class="appearance-none bg-white border border-gray-200 rounded-lg pl-5 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                <option value="all">All statuses</option>
                <option value="low">Running low</option>
                <option value="safe">Safe</option>
            </select>
            <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Raw Materials</th>
                    <th class="px-6 py-4 font-medium">In stock</th>
                    <th class="px-6 py-4 font-medium">Minimum</th>
                    <th class="px-6 py-4 font-medium">Supplier</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium">Last updated</th>
                    <th class="px-6 py-4 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="stockBody">
                @foreach ($materials as $m)
                    <tr data-name="{{ strtolower($m->nama_bahan) }}" data-status="{{ $m->is_low ? 'low' : 'safe' }}">
                        <td class="px-6 py-5">{{ $m->nama_bahan }}</td>
                        <td class="px-6 py-5">{{ RawMaterial::formatNumber($m->stok_saat_ini) }} {{ $m->satuan }}</td>
                        <td class="px-6 py-5">{{ RawMaterial::formatNumber($m->stok_minimum) }} {{ $m->satuan }}</td>
                        <td class="px-6 py-5">{{ $m->supplier?->nama_supplier ?? '-' }}</td>
                        <td class="px-6 py-5">
                            @if ($m->is_low)
                                <span class="inline-block whitespace-nowrap px-3 py-1 rounded-full text-xs bg-amber-50 text-amber-700">Running low</span>
                            @else
                                <span class="inline-block whitespace-nowrap px-3 py-1 rounded-full text-xs bg-teal-50 text-teal-700">Safe</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">{{ $m->updated_label }}</td>
                        <td class="px-6 py-5">
                            <button type="button"
                                class="js-edit-min inline-flex items-center gap-1.5 whitespace-nowrap border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold hover:bg-gray-50"
                                data-id="{{ $m->id_bahan }}"
                                data-action="{{ route('owner.inventory.minimum', ['material' => $m->id_bahan]) }}"
                                data-name="{{ $m->nama_bahan }}"
                                data-unit="{{ $m->satuan }}"
                                data-stock="{{ RawMaterial::formatNumber($m->stok_saat_ini) }}"
                                data-minimum="{{ rtrim(rtrim(number_format($m->stok_minimum, 3, '.', ''), '0'), '.') }}">
                                <i class="ti ti-edit"></i> Edit minimum
                            </button>
                        </td>
                    </tr>
                @endforeach
                <tr id="emptyRow" class="{{ $materials->isEmpty() ? '' : 'hidden' }}">
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">No ingredients found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-5">
        <a href="{{ $restockUrl }}"
            class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-5 py-3">
            <i class="ti ti-plus"></i> Request a Restock
        </a>
        <p class="text-sm text-gray-500">Inventory is updated once receipt of materials is confirmed.</p>
    </div>

    {{-- Bottom cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl">Latest usage</h2>
            <p class="text-sm text-gray-500 mb-5">
                {{ ($latestUsage->first()?->waktu ?? now())->locale('en')->isoFormat('MMMM D, YYYY') }}
            </p>
            @forelse ($latestUsage as $log)
                <div class="flex items-center justify-between text-gray-500 {{ !$loop->last ? 'mb-4' : '' }}">
                    <span>{{ $log->material->nama_bahan }} · {{ $log->waktu->format('H.i') }}</span>
                    <span class="text-[#12332d]">-{{ RawMaterial::formatNumber($log->jumlah) }}
                        {{ $log->material->satuan }}</span>
                </div>
            @empty
                <p class="text-gray-400">No usage recorded yet.</p>
            @endforelse
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl">Minimum stock level</h2>
            <p class="text-sm text-gray-500 mb-5">A warning appears when inventory falls below this threshold.</p>
            <p class="text-gray-500 mb-5">The minimum threshold helps ensure there are enough materials for daily
                operations. Change the threshold in the material details.</p>
            <a href="#" class="text-teal-700 font-semibold hover:underline">See the inventory rules <i
                    class="ti ti-arrow-right"></i></a>
        </div>
    </div>

    {{-- Pop-up Edit minimum stock --}}
    @php
        // Setelah validasi gagal, pop-up dibuka lagi untuk bahan yang sama
        $editId = old('edit_material');
        $editing = $editId ? $materials->firstWhere('id_bahan', (int) $editId) : null;
    @endphp
    <div id="minModal" class="{{ $errors->has('stok_minimum') ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="minModalTitle">
        <div class="absolute inset-0 bg-black/40" data-close-modal></div>
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-start justify-between gap-4 mb-1">
                <h2 id="minModalTitle" class="text-xl font-semibold text-[#12332d]">Edit minimum stock</h2>
                <button type="button" class="text-gray-500 hover:text-gray-800" aria-label="Close" data-close-modal>
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-5">A warning appears when stock falls to or below this level.</p>

            <form id="minForm" method="POST" action="{{ $editing ? route('owner.inventory.minimum', ['material' => $editing->id_bahan]) : '#' }}" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="edit_material" id="minEditId" value="{{ $editing?->id_bahan }}">

                <div class="bg-[#eef3f2] rounded-xl px-4 py-3 mb-5 text-sm">
                    <p class="font-semibold" id="minName">{{ $editing?->nama_bahan }}</p>
                    <p class="text-gray-500">Current stock: <span id="minStock">{{ $editing ? RawMaterial::formatNumber($editing->stok_saat_ini) : '' }}</span> <span class="min-unit">{{ $editing?->satuan }}</span></p>
                </div>

                <label for="stok_minimum" class="block text-sm font-medium mb-2">Minimum stock</label>
                <div class="relative">
                    <input type="number" id="stok_minimum" name="stok_minimum" step="any" min="0" inputmode="decimal"
                        value="{{ old('stok_minimum') }}"
                        class="w-full border border-gray-200 rounded-lg px-4 py-3 pr-20 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('stok_minimum') border-red-500 @enderror">
                    <span class="min-unit absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500">{{ $editing?->satuan }}</span>
                </div>
                @error('stok_minimum')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" data-close-modal
                        class="border border-gray-200 rounded-lg px-5 py-3 text-sm font-semibold hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="bg-teal-700 hover:bg-teal-800 text-white rounded-lg px-5 py-3 text-sm font-semibold">Save minimum</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const search = document.getElementById('search');
        const filter = document.getElementById('statusFilter');
        const rows = document.querySelectorAll('#stockBody tr[data-name]');
        const emptyRow = document.getElementById('emptyRow');

        function applyFilter() {
            const q = search.value.trim().toLowerCase();
            const s = filter.value;
            let visible = 0;
            rows.forEach(r => {
                const show = r.dataset.name.includes(q) && (s === 'all' || r.dataset.status === s);
                r.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            emptyRow.classList.toggle('hidden', visible > 0);
        }
        search.addEventListener('input', applyFilter);
        filter.addEventListener('change', applyFilter);

        // ---- Pop-up Edit minimum stock ----
        const modal = document.getElementById('minModal');
        const minForm = document.getElementById('minForm');
        const minInput = document.getElementById('stok_minimum');

        function openModal(btn) {
            minForm.action = btn.dataset.action;
            document.getElementById('minEditId').value = btn.dataset.id;
            document.getElementById('minName').textContent = btn.dataset.name;
            document.getElementById('minStock').textContent = btn.dataset.stock;
            modal.querySelectorAll('.min-unit').forEach(el => el.textContent = btn.dataset.unit);
            minInput.value = btn.dataset.minimum;
            modal.classList.remove('hidden');
            minInput.focus();
            minInput.select();
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        document.querySelectorAll('.js-edit-min').forEach(btn => btn.addEventListener('click', () => openModal(btn)));
        modal.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', closeModal));
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    </script>
@endpush