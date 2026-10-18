@extends('layouts.supplier')

@section('title', 'Shipments')
@section('heading', 'Shipments')
@section('subheading', 'Prepare shipments for confirmed orders and follow the ones on the way.')

@php
    use App\Models\RawMaterial;
    use App\Models\RestockOrder;

    $badge = [
        'dikonfirmasi' => 'bg-sky-50 text-sky-700',
        'dikirim' => 'bg-blue-50 text-blue-700',
        'diterima' => 'bg-teal-50 text-teal-700',
    ];

    $tabs = [
        '' => 'All',
        'dikonfirmasi' => 'Ready to ship',
        'dikirim' => 'On the way',
        'diterima' => 'Delivered',
    ];

    $totalAll = array_sum($counts);
@endphp

@section('content')
    {{-- Status tabs --}}
    <div class="inline-flex flex-wrap bg-white border border-gray-200 rounded-xl p-1 mb-6">
        @foreach ($tabs as $key => $label)
            @php
                $isActive = (string) $status === (string) $key;
                $count = $key === '' ? $totalAll : $counts[$key];
            @endphp
            <a href="{{ route('supplier.shipments.index', $key === '' ? [] : ['status' => $key]) }}"
                class="px-5 py-2.5 rounded-lg text-sm {{ $isActive ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-500 hover:text-gray-800' }}">
                {{ $label }} <span class="ml-1 text-xs">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Order</th>
                    <th class="px-6 py-4 font-medium">Material</th>
                    <th class="px-6 py-4 font-medium">Carrier / courier</th>
                    <th class="px-6 py-4 font-medium">Tracking number</th>
                    <th class="px-6 py-4 font-medium">Estimated arrival</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $o)
                    @php
                        $first = $o->details->first();
                        $more = $o->details->count() - 1;
                    @endphp
                    <tr>
                        <td class="px-6 py-5 font-semibold whitespace-nowrap">{{ $o->kode_restock }}</td>
                        <td class="px-6 py-5">
                            <p>{{ $first?->material->nama_bahan }}@if ($more > 0)
                                    <span class="text-gray-400">+{{ $more }} more</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if ($more > 0)
                                    {{ $o->details->count() }} items
                                @else
                                    {{ RawMaterial::formatNumber($first->jumlah) }} {{ $first->material->satuan }}
                                @endif
                            </p>
                        </td>
                        <td class="px-6 py-5">
                            @if ($o->ekspedisi)
                                <p>{{ $o->ekspedisi }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $o->nama_kurir }}</p>
                            @else
                                <span class="text-gray-400">Not set yet</span>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">{{ $o->nomor_resi ?: '-' }}</td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            {{ $o->estimasi_tiba ? $o->estimasi_tiba->format('j M Y, H.i') : ($o->perkiraan_kirim ? $o->perkiraan_kirim->format('j M Y, H.i') . ' (planned)' : '-') }}
                        </td>
                        <td class="px-6 py-5">
                            <span
                                class="px-3 py-1 rounded-full text-xs whitespace-nowrap {{ $badge[$o->status] }}">{{ $o->status_label }}</span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <a href="{{ route('supplier.shipments.edit', $o) }}"
                                class="font-semibold text-teal-700 hover:underline">
                                {{ $o->status === 'dikonfirmasi' ? 'Set Shipping' : 'View Shipment' }} <i
                                    class="ti ti-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                                No shipments yet. Orders appear here once you confirm them.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mt-5 text-sm">
            <p class="text-gray-500">
                @if ($orders->total() > 0)
                    Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} shipments
                @else
                    No shipments
                @endif
            </p>

            <div class="flex items-center gap-2">
                @if ($orders->previousPageUrl())
                    <a href="{{ $orders->previousPageUrl() }}"
                        class="px-5 py-3 rounded-lg bg-white border border-gray-200 font-semibold hover:bg-gray-50">Previous</a>
                @else
                    <span class="px-5 py-3 rounded-lg bg-gray-100 text-gray-400 font-semibold">Previous</span>
                @endif

                <span class="px-4 py-3 rounded-lg bg-teal-50 text-teal-700 font-semibold">{{ $orders->currentPage() }}</span>

                @if ($orders->nextPageUrl())
                    <a href="{{ $orders->nextPageUrl() }}"
                        class="px-5 py-3 rounded-lg bg-white border border-gray-200 font-semibold hover:bg-gray-50">Next</a>
                @else
                    <span class="px-5 py-3 rounded-lg bg-gray-100 text-gray-400 font-semibold">Next</span>
                @endif
            </div>
        </div>

        <div class="mt-8 bg-teal-50 rounded-xl px-6 py-4 flex items-start gap-4">
            <i class="ti ti-circle-check text-2xl text-teal-700 mt-0.5"></i>
            <div>
                <p class="font-semibold text-teal-800">Receipt is not marked by the supplier</p>
                <p class="text-sm text-teal-800">The Delivered status is confirmed by the laundry after the quantity, item
                    condition, and delivery note are checked.</p>
            </div>
        </div>
    @endsection
