@extends('layouts.owner')

@section('title', 'Report')
@section('heading', 'Report')
@section('subheading', 'Review transactions for the selected period, then download them for bookkeeping.')

@php
    use App\Models\Order;

    $exportQuery = [
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $end->format('Y-m-d'),
        'type' => $type,
    ];
@endphp

@section('actions')
    <details class="relative">
        <summary
            class="list-none [&::-webkit-details-marker]:hidden cursor-pointer inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-6 py-3.5">
            <i class="ti ti-download"></i> Download the Report
        </summary>
        <div class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden z-10">
            <a href="{{ route('owner.reports.export', $exportQuery) }}"
                class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-gray-50">
                <i class="ti ti-file-spreadsheet"></i> Excel (.csv)
            </a>
            <a href="{{ route('owner.reports.print', $exportQuery) }}" target="_blank"
                class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-gray-50">
                <i class="ti ti-file-type-pdf"></i> PDF
            </a>
        </div>
    </details>
@endsection

@section('content')
    {{-- Filters --}}
    <form method="GET" action="{{ route('owner.reports.index') }}"
        class="bg-white border border-gray-200 rounded-2xl p-6 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label for="start_date" class="block text-sm mb-1.5">Start date</label>
            <input type="date" name="start_date" id="start_date" value="{{ $start->format('Y-m-d') }}"
                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('start_date') border-red-500 @enderror">
            @error('start_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="end_date" class="block text-sm mb-1.5">End date</label>
            <input type="date" name="end_date" id="end_date" value="{{ $end->format('Y-m-d') }}"
                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('end_date') border-red-500 @enderror">
            @error('end_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="type" class="block text-sm mb-1.5">Types of reports</label>
            <div class="relative">
                <select name="type" id="type"
                    class="w-full appearance-none border border-gray-200 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
        </div>

        <button type="submit" class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-3 text-sm">
            Display Report
        </button>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Total orders</span><i class="ti ti-clipboard-list text-xl text-teal-700"></i>
            </div>
            <p class="text-4xl font-bold my-3">{{ $summary['orders'] }}</p>
            <p class="text-sm text-gray-500">{{ $periodLabel }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Revenue</span><i class="ti ti-wallet text-xl text-teal-700"></i>
            </div>
            <p class="text-4xl font-bold my-3">{{ Order::rupiah($summary['revenue']) }}</p>
            <p class="text-sm {{ $summary['unpaid'] > 0 ? 'text-amber-700' : 'text-gray-500' }}">
                {{ $summary['unpaid'] > 0 ? $summary['unpaid'] . ' unpaid orders are not counted' : 'All payments have been paid off' }}
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Total weight</span><i class="ti ti-shopping-bag text-xl text-teal-700"></i>
            </div>
            <p class="text-4xl font-bold my-3">{{ Order::kg($summary['weight']) }} kg</p>
            <p class="text-sm text-gray-500">Average of {{ Order::kg(round($summary['average_weight'], 2)) }} kg per order
            </p>
        </div>
    </div>

    {{-- Transactions --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl">{{ $typeLabel }}</h2>
            <p class="text-sm text-gray-500">{{ $periodLabel }} • {{ $summary['orders'] }} transactions</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('owner.reports.export', $exportQuery) }}"
                class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
                <i class="ti ti-file-spreadsheet"></i> Download Excel
            </a>
            <a href="{{ route('owner.reports.print', $exportQuery) }}" target="_blank"
                class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
                <i class="ti ti-file-type-pdf"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Date</th>
                    <th class="px-6 py-4 font-medium">Order code</th>
                    <th class="px-6 py-4 font-medium">Customer</th>
                    <th class="px-6 py-4 font-medium">Service</th>
                    <th class="px-6 py-4 font-medium">Weight</th>
                    <th class="px-6 py-4 font-medium">Revenue</th>
                    <th class="px-6 py-4 font-medium">Paid</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $o)
                    <tr>
                        <td class="px-6 py-5 whitespace-nowrap">{{ $o->waktu_pesanan->format('M. j, Y') }}</td>
                        <td class="px-6 py-5 whitespace-nowrap">{{ $o->kode_pesanan }}</td>
                        <td class="px-6 py-5">{{ $o->customer->nama }}</td>
                        <td class="px-6 py-5">{{ $o->service->nama_layanan }}</td>
                        <td class="px-6 py-5 whitespace-nowrap">{{ Order::kg($o->berat_kg) }} kg</td>
                        <td class="px-6 py-5 font-semibold">{{ Order::rupiah($o->total_harga) }}</td>
                        <td class="px-6 py-5">
                            @if ($o->is_paid)
                                <span
                                    class="px-3 py-1 rounded-full text-xs bg-teal-50 text-teal-700 whitespace-nowrap">Fully
                                    paid</span>
                            @else
                                <span
                                    class="px-3 py-1 rounded-full text-xs bg-amber-50 text-amber-700 whitespace-nowrap">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">No transactions in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 bg-teal-50 rounded-xl px-6 py-4 flex items-center justify-between">
        <span class="font-semibold">Total • {{ $summary['orders'] }} orders • {{ Order::kg($summary['weight']) }}
            kg</span>
        <span class="font-semibold text-teal-700">{{ Order::rupiah($summary['revenue']) }}</span>
    </div>

    {{-- Bottom cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl mb-5">Revenue breakdown</h2>
            <div class="space-y-4 text-gray-500">
                <div class="flex items-center justify-between">
                    <span>Main Services</span>
                    <span class="text-[#12332d]">{{ Order::rupiah($breakdown['main']) }}</span>
                </div>
                @foreach ($breakdown['addons'] as $addon)
                    <div class="flex items-center justify-between">
                        <span>{{ $addon->name }}</span>
                        <span class="text-[#12332d]">{{ Order::rupiah($addon->total) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl mb-5">Report Notes</h2>
            <p class="text-gray-500 mb-4">
                Revenue includes services and add-ons for {{ $summary['orders'] }} orders during this period. There were no
                cancellations or refunds.
            </p>
            <p class="text-sm text-gray-500">The data is the same as the Dashboard summary.</p>
        </div>
    </div>
@endsection
