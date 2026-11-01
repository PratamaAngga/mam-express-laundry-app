@extends('layouts.owner')

@section('title', 'Restock Supplier')
@section('heading', 'Restock Supplier')
@section('subheading', 'Submit the materials and track the shipment until it is received at the branch.')

@section('actions')
    <a href="{{ route('owner.restock.create') }}"
        class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-6 py-3.5">
        <i class="ti ti-plus"></i> Ajukan Restock
    </a>
@endsection

@section('content')
    @php
        $rupiah = fn($v) => \App\Models\RestockOrder::rupiah($v);

        $steps = [
            ['label' => 'Submitted', 'icon' => 'ti-clipboard-list'],
            ['label' => 'Confirmed', 'icon' => 'ti-circle-check'],
            ['label' => 'Sent', 'icon' => 'ti-truck-delivery'],
            ['label' => 'Accepted', 'icon' => 'ti-package'],
        ];
        $currentIndex = $selected ? array_search($selected->status, \App\Models\RestockOrder::FLOW) : -1;

        $badge = [
            'diajukan' => 'bg-amber-50 text-amber-700',
            'dikonfirmasi' => 'bg-sky-50 text-sky-700',
            'dikirim' => 'bg-blue-50 text-blue-700',
            'diterima' => 'bg-teal-50 text-teal-700',
        ];

        $banner = [
            'diajukan' => [
                'amber',
                'ti-alert-triangle',
                'Waiting for confirmation from the supplier',
                'An estimated arrival time will be available once the request is confirmed.',
            ],
            'dikonfirmasi' => [
                'teal',
                'ti-circle-check',
                'Confirmed by the supplier',
                'The supplier is preparing your items.',
            ],
            'dikirim' => [
                'blue',
                'ti-truck-delivery',
                'Items are on the way',
                'Confirm the receipt once the shipment arrives at the branch.',
            ],
            'diterima' => [
                'teal',
                'ti-circle-check',
                'Received at the branch',
                'The items have been received and the stock is added.',
            ],
        ];
        $bannerTone = [
            'amber' => ['bg-amber-50', 'text-amber-800', 'text-amber-700'],
            'teal' => ['bg-teal-50', 'text-teal-800', 'text-teal-700'],
            'blue' => ['bg-blue-50', 'text-blue-800', 'text-blue-700'],
        ];
    @endphp

    {{-- Flow steps --}}
    <div class="bg-white border border-gray-200 rounded-2xl px-6 py-5 mb-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($steps as $i => $step)
            @php $active = $i <= $currentIndex; @endphp
            <div class="flex items-center gap-3">
                <div
                    class="w-11 h-11 rounded-full flex items-center justify-center text-xl {{ $active ? 'bg-teal-50 text-teal-700' : 'bg-gray-100 text-gray-400' }}">
                    <i class="ti {{ $step['icon'] }}"></i>
                </div>
                <span class="font-medium">{{ $step['label'] }}</span>
                @if (!$loop->last)
                    <i class="ti ti-arrow-right text-gray-400"></i>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left: list --}}
        <div class="xl:col-span-2 space-y-6">
            <form method="GET" class="flex flex-wrap items-center justify-between gap-3">
                <div class="relative w-full max-w-md">
                    <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    <input type="text" name="q" value="{{ $q }}"
                        placeholder="Search for a number or supplier"
                        class="w-full bg-white border border-gray-200 rounded-lg pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                </div>
                <div class="relative">
                    <select name="status" onchange="this.form.submit()"
                        class="appearance-none bg-white border border-gray-200 rounded-lg pl-5 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\RestockOrder::LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </form>

            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#eef3f2] text-left text-gray-500">
                        <tr>
                            <th class="px-6 py-4 font-medium">Ordering</th>
                            <th class="px-6 py-4 font-medium">Supplier</th>
                            <th class="px-6 py-4 font-medium">Total</th>
                            <th class="px-6 py-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $o)
                            @php
                                $isSelected = $selected && $selected->id_restock === $o->id_restock;
                                $url = route(
                                    'owner.restock.index',
                                    array_filter(['order' => $o->kode_restock, 'q' => $q, 'status' => $status]),
                                );
                            @endphp
                            <tr onclick="window.location='{{ $url }}'"
                                class="cursor-pointer hover:bg-gray-50 {{ $isSelected ? 'bg-teal-50/40' : '' }}">
                                <td class="px-6 py-4">
                                    <p class="{{ $isSelected ? 'text-teal-700 font-medium' : 'font-semibold' }}">
                                        {{ $o->kode_restock }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $o->tanggal_pengajuan->format($o->status === 'diajukan' ? 'j M Y • H.i' : 'j M Y') }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">{{ $o->supplier->nama_supplier }}</td>
                                <td class="px-6 py-4">{{ $rupiah($o->total) }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs {{ $badge[$o->status] }}">{{ $o->status_label }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">No restock requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-sm text-gray-500">{{ $orders->count() }} pemesanan • Pilih pemesanan untuk melihat rinciannya.
            </p>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl mb-5">Registered supplier</h2>
                <div class="space-y-4 text-gray-500">
                    @foreach ($suppliers as $s)
                        <div class="flex items-center justify-between">
                            <span>{{ $s->nama_supplier }}</span>
                            <span class="text-[#12332d]">{{ $s->whatsapp_label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: details --}}
        <aside class="bg-white border border-gray-200 rounded-2xl p-6 h-fit">
            @if ($selected)
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl">Application Details</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $selected->kode_restock }}</p>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs {{ $badge[$selected->status] }}">{{ $selected->status_label }}</span>
                </div>

                <dl class="mt-6 space-y-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Supplier</dt>
                        <dd class="font-medium text-right">{{ $selected->supplier->nama_supplier }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Submitted by</dt>
                        <dd class="font-medium text-right">{{ $selected->owner->nama }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Date of submission</dt>
                        <dd class="font-medium text-right">{{ $selected->tanggal_pengajuan->format('j M Y, H.i') }}</dd>
                    </div>
                </dl>

                <div class="border-t border-gray-200 mt-5 pt-5 space-y-4">
                    @foreach ($selected->details as $d)
                        <div>
                            <p class="font-semibold">{{ $d->material->nama_bahan }}</p>
                            <div class="flex justify-between text-sm mt-1">
                                <span class="text-gray-500">
                                    {{ \App\Models\RawMaterial::formatNumber($d->jumlah) }} {{ $d->material->satuan }} ×
                                    {{ $rupiah($d->harga_satuan) }}
                                </span>
                                <span class="font-semibold">{{ $rupiah($d->subtotal) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 mt-5 pt-5 flex justify-between font-semibold">
                    <span>Total applications</span>
                    <span class="font-normal">{{ $rupiah($selected->total) }}</span>
                </div>

                <p class="text-sm mt-5 mb-2">Note to the supplier</p>
                <div class="border border-gray-200 rounded-lg px-4 py-3 text-sm">{{ $selected->catatan ?: '-' }}</div>

                @php
                    [$tone, $icon, $title, $text] = $banner[$selected->status];
                    [$bg, $titleColor, $iconColor] = $bannerTone[$tone];
                @endphp
                <div class="mt-5 {{ $bg }} rounded-xl px-5 py-4 flex gap-3">
                    <i class="ti {{ $icon }} text-2xl {{ $iconColor }}"></i>
                    <div>
                        <p class="font-semibold {{ $titleColor }}">{{ $title }}</p>
                        <p class="text-sm {{ $titleColor }}">{{ $text }}</p>
                    </div>
                </div>

                <h3 class="font-semibold mt-6 mb-4">The Booking Process</h3>
                <ol class="space-y-4">
                    @foreach ($selected->timeline() as $step)
                        <li class="flex gap-3">
                            <span
                                class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 {{ $step['done'] ? 'bg-teal-700' : 'bg-gray-200' }}"></span>
                            <div class="flex-1 flex justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold">{{ $step['label'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $step['description'] }}</p>
                                </div>
                                <span class="text-xs text-gray-500 whitespace-nowrap">
                                    {{ $step['time'] ? $step['time']->format('j M, H.i') : 'Menunggu' }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <a href="{{ $selected->supplier->whatsapp_url }}" target="_blank" rel="noopener"
                    class="mt-6 w-full flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-3 text-sm font-semibold hover:bg-gray-50">
                    <i class="ti ti-message-circle"></i> Contact the Supplier
                </a>
            @else
                <p class="text-gray-400 text-center py-10">Select a request to see the details.</p>
            @endif
        </aside>
    </div>
@endsection
