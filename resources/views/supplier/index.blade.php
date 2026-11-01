@extends('layouts.supplier')

@section('title', 'Restock Orders')
@section('heading', 'Restock Orders')
@section('subheading', 'Review material requests, confirm orders, and track your shipments.')

@php
    use App\Models\RawMaterial;
    use App\Models\RestockOrder;

    $rupiah = fn($v) => RestockOrder::rupiah($v);

    $badge = [
        'diajukan' => 'bg-amber-50 text-amber-700',
        'dikonfirmasi' => 'bg-sky-50 text-sky-700',
        'dikirim' => 'bg-blue-50 text-blue-700',
        'diterima' => 'bg-teal-50 text-teal-700',
    ];

    $actionLabel = [
        'diajukan' => 'Confirm',
        'dikonfirmasi' => 'Set Shipping',
        'dikirim' => 'View Shipment',
        'diterima' => 'View detail',
    ];

    $activeStep = in_array($status, RestockOrder::FLOW, true) ? $status : 'diajukan';
    $location = 'Candi Panggung, Malang';
@endphp

@section('content')
    {{-- Info banner --}}
    <div class="mb-6 bg-teal-50 rounded-xl px-6 py-4 flex items-start gap-4">
        <i class="ti ti-circle-check text-2xl text-teal-700 mt-0.5"></i>
        <div>
            <p class="font-semibold text-teal-800">{{ $supplier->nama_supplier }}</p>
            <p class="text-sm text-teal-800">Only orders addressed to {{ $supplier->nama_supplier }} are shown in this
                supplier room.</p>
        </div>
    </div>

    {{-- Status counts --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach (RestockOrder::LABELS as $key => $label)
            <div class="bg-white border border-gray-200 rounded-2xl px-6 py-5 flex items-center justify-between">
                <span class="text-gray-500">{{ $label }}</span>
                <span class="text-3xl">{{ $counts[$key] }}</span>
            </div>
        @endforeach
    </div>

    {{-- Flow steps --}}
    <div class="bg-white border border-gray-200 rounded-2xl px-6 py-5 mb-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach (RestockOrder::LABELS as $key => $label)
            @php $isActive = $key === $activeStep; @endphp
            <div class="flex items-center gap-3">
                <span
                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm {{ $isActive ? 'border-2 border-teal-700 text-teal-700 bg-white font-semibold' : 'bg-gray-100 text-gray-500' }}">{{ $loop->iteration }}</span>
                <span class="{{ $isActive ? 'font-semibold' : 'text-gray-500' }}">{{ $label }}</span>
                @if (!$loop->last)
                    <i class="ti ti-arrow-right text-gray-400 ml-auto hidden lg:block"></i>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Search & filters --}}
    <form method="GET" class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="relative w-full max-w-md">
            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            <input type="text" name="q" value="{{ $q }}"
                placeholder="Search by restock number or material"
                class="w-full bg-white border border-gray-200 rounded-lg pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <select name="status" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-5 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                    <option value="">All statuses</option>
                    @foreach (RestockOrder::LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>

            <div class="relative">
                <i class="ti ti-calendar absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <select name="period" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-11 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                    <option value="all" @selected($period === 'all')>All time</option>
                    <option value="30" @selected($period === '30')>Last 30 days</option>
                    <option value="90" @selected($period === '90')>Last 90 days</option>
                </select>
                <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Order</th>
                    <th class="px-6 py-4 font-medium">Material / destination</th>
                    <th class="px-6 py-4 font-medium">Quantity</th>
                    <th class="px-6 py-4 font-medium">Total</th>
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
                        <td class="px-6 py-5">
                            <p class="font-semibold">{{ $o->kode_restock }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $o->tanggal_pengajuan->format('j M Y') }}{{ $o->status !== 'diterima' ? ' • ' . $o->tanggal_pengajuan->format('H.i') : '' }}
                            </p>
                        </td>
                        <td class="px-6 py-5">
                            <p>{{ $first?->material->nama_bahan }}@if ($more > 0)
                                    <span class="text-gray-400">+{{ $more }} more</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $location }}</p>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if ($more > 0)
                                {{ $o->details->count() }} items
                            @else
                                {{ RawMaterial::formatNumber($first->jumlah) }} {{ $first->material->satuan }}
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">{{ $rupiah($o->total) }}</td>
                        <td class="px-6 py-5">
                            <span
                                class="px-3 py-1 rounded-full text-xs whitespace-nowrap {{ $badge[$o->status] }}">{{ $o->status_label }}</span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <a href="{{ route('supplier.restock.show', $o) }}"
                                class="font-semibold text-teal-700 hover:underline">
                                {{ $actionLabel[$o->status] }} <i class="ti ti-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">No matching orders.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mt-5 text-sm">
            <p class="text-gray-500">
                @if ($orders->total() > 0)
                    Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders
                @else
                    No orders
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

        {{-- Bottom cards --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-5">Laundry order contact</h2>
                <div class="flex items-center justify-between mb-5">
                    <span class="text-gray-500">Manager</span>
                    <span class="font-semibold">{{ $manager?->nama ?? '-' }} • Mam Express</span>
                </div>
                <p class="text-sm text-gray-500">Coordinate any changes in quantity, price, or schedule before confirming the
                    order.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-5">Receipt confirmation</h2>
                <p class="text-gray-500">Suppliers update the status up to Sent. The Accepted status is recorded by the laundry
                    once the items have been checked at the destination.</p>
            </div>
        </div>
    @endsection
