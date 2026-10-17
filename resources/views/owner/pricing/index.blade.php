@extends('layouts.owner')

@section('title', 'Service Pricing')
@section('heading', 'Service Pricing')
@section('subheading', 'The rates used by cashiers when taking orders.')

@php
    use App\Models\Service;

    $isNew = $editing === null;
    $firstAddon = $additional->first();

    // Values for the edit panel (old input wins after a validation error)
    $panelName = old('nama_layanan', $editing?->nama_layanan ?? '');
    $panelPrice = (int) old('harga_per_kg', $editing?->harga_per_kg ?? 0);
    $panelDays = (int) old('estimasi_hari', $editing?->estimasi_hari ?? 2);
    $panelStatus = (int) old('status_aktif', $editing ? (int) $editing->status_aktif : 1);

    // Cashier's calculation
$addonPrice = $firstAddon ? (int) old('prices.' . $firstAddon->id_tambahan, $firstAddon->harga) : 0;
    $serviceTotal = (int) round($weight * $panelPrice);
@endphp

@section('actions')
    @if ($additional->isNotEmpty())
        <button type="submit" form="additional-form"
            class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-6 py-3.5">
            <i class="ti ti-check"></i> Save Changes
        </button>
    @endif
@endsection

@section('content')
    {{-- Info banner --}}
    <div class="mb-8 bg-teal-50 rounded-xl px-6 py-4 flex items-start gap-4">
        <i class="ti ti-circle-check text-2xl text-teal-700 mt-0.5"></i>
        <div>
            <p class="font-semibold text-teal-800">Current prices for new orders</p>
            <p class="text-sm text-teal-800">Rate changes do not affect the prices of orders that have already been saved.
            </p>
        </div>
    </div>

    {{-- Key services --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl">Key Services</h2>
        <a href="{{ route('owner.pricing.index', ['new' => 1]) }}"
            class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
            <i class="ti ti-plus"></i> Add Service
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Service Name</th>
                    <th class="px-6 py-4 font-medium">Price per kilogram</th>
                    <th class="px-6 py-4 font-medium">Estimated completion</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($services as $s)
                    <tr class="{{ $editing && $editing->is($s) ? 'bg-teal-50/40' : '' }}">
                        <td class="px-6 py-5">{{ $s->nama_layanan }}</td>
                        <td class="px-6 py-5">{{ Service::rupiah($s->harga_per_kg) }}</td>
                        <td class="px-6 py-5">{{ $s->days_label }}</td>
                        <td class="px-6 py-5">
                            @if ($s->status_aktif)
                                <span class="px-3 py-1 rounded-full text-xs bg-teal-50 text-teal-700">Active</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <a href="{{ route('owner.pricing.index', ['edit' => $s->id_layanan]) }}"
                                class="font-semibold text-teal-700 hover:underline">Change price <i
                                    class="ti ti-arrow-right"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">No services yet. Click "Add Service"
                            to create one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left: additional services + calculation --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl">Additional Services</h2>
                <p class="text-sm text-gray-500 mb-5">Applies once per order.</p>

                <form id="additional-form" method="POST" action="{{ route('owner.pricing.additional') }}">
                    @csrf
                    @method('PATCH')

                    @forelse ($additional as $a)
                        @php $value = (int) old('prices.' . $a->id_tambahan, $a->harga); @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 {{ !$loop->last ? 'mb-4' : '' }}">
                            <div>
                                <label class="block text-sm mb-1.5">{{ $a->nama_tambahan }}</label>
                                <input type="text" inputmode="numeric" data-rupiah="addon-{{ $a->id_tambahan }}"
                                    value="{{ $value > 0 ? Service::rupiah($value) : '' }}"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('prices.' . $a->id_tambahan) border-red-500 @enderror">
                                <input type="hidden" name="prices[{{ $a->id_tambahan }}]"
                                    id="addon-{{ $a->id_tambahan }}" value="{{ $value }}"
                                    @if ($loop->first) data-calc-addon @endif>
                                @error('prices.' . $a->id_tambahan)
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm mb-1.5">Unit cost</label>
                                <div class="relative">
                                    <select disabled
                                        class="w-full appearance-none border border-gray-200 rounded-lg px-4 py-3 pr-10 text-sm bg-gray-50 text-gray-600">
                                        <option>Per order</option>
                                    </select>
                                    <i
                                        class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400">No additional services yet.</p>
                    @endforelse
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl mb-5">Cashier's calculation</h2>

                <div class="space-y-4 text-gray-500">
                    <div class="flex items-center justify-between gap-4">
                        <span id="calc-service-line">
                            {{ $panelName !== '' ? $panelName : 'New service' }} · {{ $weight }} kg ×
                            {{ number_format($panelPrice, 0, '.', ',') }} IDR
                        </span>
                        <span id="calc-service-total"
                            class="text-[#12332d] font-semibold">{{ Service::rupiah($serviceTotal) }}</span>
                    </div>

                    @if ($firstAddon)
                        <div class="flex items-center justify-between gap-4">
                            <span>{{ $firstAddon->nama_tambahan }}</span>
                            <span id="calc-addon-total"
                                class="text-[#12332d] font-semibold">{{ Service::rupiah($addonPrice) }}</span>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-200 mt-5 pt-5 flex items-center justify-between">
                    <span class="font-semibold">Total payment</span>
                    <span id="calc-total" class="text-gray-500">{{ Service::rupiah($serviceTotal + $addonPrice) }}</span>
                </div>
            </div>
        </div>

        {{-- Right: change / add service --}}
        <aside class="bg-white border border-gray-200 rounded-2xl p-6 h-fit">
            <h2 class="text-xl">{{ $isNew ? 'Add service' : 'Change service' }}</h2>
            <p class="text-sm text-gray-500 mb-6">{{ $isNew ? 'New service' : $editing->nama_layanan }}</p>

            <form method="POST"
                action="{{ $isNew ? route('owner.pricing.store') : route('owner.pricing.update', $editing) }}"
                class="space-y-5">
                @csrf
                @if (!$isNew)
                    @method('PATCH')
                @endif

                <div>
                    <label for="service-name" class="block text-sm mb-1.5">Service Name</label>
                    <input type="text" name="nama_layanan" id="service-name" value="{{ $panelName }}" maxlength="50"
                        class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('nama_layanan') border-red-500 @enderror">
                    @error('nama_layanan')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price-display" class="block text-sm mb-1.5">Price per kilogram</label>
                    <input type="text" inputmode="numeric" id="price-display" data-rupiah="service-price"
                        value="{{ $panelPrice > 0 ? Service::rupiah($panelPrice) : '' }}"
                        class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('harga_per_kg') border-red-500 @enderror">
                    <input type="hidden" name="harga_per_kg" id="service-price" value="{{ $panelPrice }}">
                    @error('harga_per_kg')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="estimasi_hari" class="block text-sm mb-1.5">Estimated completion</label>
                    <div class="relative">
                        <select name="estimasi_hari" id="estimasi_hari"
                            class="w-full appearance-none border border-gray-200 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('estimasi_hari') border-red-500 @enderror">
                            @foreach (range(1, max(7, $panelDays)) as $d)
                                <option value="{{ $d }}" @selected($panelDays === $d)>{{ $d }}
                                    {{ $d === 1 ? 'day' : 'days' }}</option>
                            @endforeach
                        </select>
                        <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                    @error('estimasi_hari')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status_aktif" class="block text-sm mb-1.5">Service Status</label>
                    <div class="relative">
                        <select name="status_aktif" id="status_aktif"
                            class="w-full appearance-none border border-gray-200 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                            <option value="1" @selected($panelStatus === 1)>Active</option>
                            <option value="0" @selected($panelStatus === 0)>Inactive</option>
                        </select>
                        <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                    @error('status_aktif')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-gray-200 pt-5 space-y-3">
                    <button type="submit"
                        class="w-full bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg py-3 text-sm">
                        {{ $isNew ? 'Add Service' : 'Apply Price' }}
                    </button>
                    <a href="{{ route('owner.pricing.index') }}"
                        class="block w-full text-center bg-white border border-gray-200 hover:bg-gray-50 font-semibold rounded-lg py-3 text-sm">
                        Cancel
                    </a>
                </div>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        const WEIGHT = {{ $weight }};

        const idr = n => 'Rp' + Number(n || 0).toLocaleString('id-ID');
        const num = id => parseInt(document.getElementById(id)?.value || '0', 10) || 0;

        const nameInput = document.getElementById('service-name');
        const addonHidden = document.querySelector('[data-calc-addon]');

        function recalc() {
            const price = num('service-price');
            const addon = addonHidden ? (parseInt(addonHidden.value || '0', 10) || 0) : 0;
            const serviceTotal = Math.round(WEIGHT * price);
            const name = nameInput.value.trim() || 'New service';

            document.getElementById('calc-service-line').textContent =
                name + ' · ' + WEIGHT + ' kg × ' + price.toLocaleString('en-US') + ' IDR';
            document.getElementById('calc-service-total').textContent = idr(serviceTotal);

            const addonEl = document.getElementById('calc-addon-total');
            if (addonEl) addonEl.textContent = idr(addon);

            document.getElementById('calc-total').textContent = idr(serviceTotal + addon);
        }

        // "Rp8.000" style inputs: show formatted text, send the plain number in a hidden field
        document.querySelectorAll('[data-rupiah]').forEach(input => {
            const hidden = document.getElementById(input.dataset.rupiah);

            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '');
                hidden.value = digits;
                input.value = digits ? idr(digits) : '';
                recalc();
            });
        });

        nameInput.addEventListener('input', recalc);
    </script>
@endpush
