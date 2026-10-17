@extends('layouts.owner')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', $greeting . ', ' . $firstName . '. Here is an operational summary for your branch.')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@php
    use App\Models\Order;

    $exportQuery = [
        'range' => 'custom',
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $end->format('Y-m-d'),
    ];

    $palette = ['#0b7f6f', '#3b6bb0', '#d99a2b', '#8b7bc8', '#9ca3af'];

    $statusBadge = [
        'antre_cuci' => 'bg-amber-50 text-amber-700',
        'diproses' => 'bg-blue-50 text-blue-700',
        'dikemas_siap_diambil' => 'bg-teal-50 text-teal-700',
        'selesai' => 'bg-emerald-50 text-emerald-700',
    ];
@endphp

@section('actions')
    <a href="{{ route('owner.dashboard.export', $exportQuery) }}"
        class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
        <i class="ti ti-download"></i> Export Summary
    </a>
@endsection

@section('content')
    {{-- Period tabs --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="inline-flex bg-white border border-gray-200 rounded-xl p-1">
            @foreach ($ranges as $key => $label)
                @php
                    $key = (string) $key;
                    $href =
                        $key === 'custom'
                            ? route('owner.dashboard', $exportQuery)
                            : route('owner.dashboard', ['range' => $key]);
                @endphp
                <a href="{{ $href }}"
                    class="px-5 py-2.5 rounded-lg text-sm {{ $range === $key ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-500 hover:text-gray-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <span class="text-gray-500">{{ $periodLabel }}</span>
    </div>

    @if ($range === 'custom')
        <form method="GET" action="{{ route('owner.dashboard') }}"
            class="bg-white border border-gray-200 rounded-2xl p-5 mb-6 flex flex-wrap items-end gap-4">
            <input type="hidden" name="range" value="custom">
            <div>
                <label for="start_date" class="block text-sm mb-1.5">Start date</label>
                <input type="date" name="start_date" id="start_date" value="{{ $start->format('Y-m-d') }}"
                    class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
            </div>
            <div>
                <label for="end_date" class="block text-sm mb-1.5">End date</label>
                <input type="date" name="end_date" id="end_date" value="{{ $end->format('Y-m-d') }}"
                    class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
            </div>
            <button type="submit"
                class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-2.5 text-sm">Apply</button>
            @error('end_date')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror
        </form>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @php
            $cards = [
                [
                    'Total orders',
                    $summary['orders'],
                    $summary['today_count'] . ' orders came in today',
                    'ti-clipboard-list',
                ],
                [
                    'Revenue',
                    Order::rupiah($summary['revenue']),
                    $summary['unpaid'] > 0
                        ? $summary['unpaid'] . ' unpaid orders are not counted'
                        : 'All payments have been paid in full',
                    'ti-wallet',
                ],
                [
                    'Average weight',
                    Order::kg(round($summary['average_weight'], 2)) . ' kg',
                    'A total of ' . Order::kg($summary['weight']) . ' kg of laundry',
                    'ti-shopping-bag',
                ],
                ['Active orders', $summary['active'], $summary['ready'] . ' orders are ready for pickup', 'ti-clock'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $note, $icon])
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>{{ $label }}</span>
                    <i class="ti {{ $icon }} text-xl text-teal-700"></i>
                </div>
                <p class="text-4xl font-bold my-3">{{ $value }}</p>
                <p
                    class="text-sm {{ $label === 'Revenue' && $summary['unpaid'] > 0 ? 'text-amber-700' : 'text-gray-500' }}">
                    {{ $note }}</p>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <div class="xl:col-span-2 bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl">Daily Transaction Trends</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $periodLabel }}</p>
            <div class="relative h-64"><canvas id="trendChart"></canvas></div>
            <p class="text-sm text-gray-500 mt-4 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-teal-700 inline-block"></span>
                Orders • total {{ $summary['orders'] }} transactions
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl">Use of the Service</h2>
            <p class="text-sm text-gray-500 mb-4">Proportion based on the number of orders</p>

            @if ($summary['orders'] > 0)
                <div class="relative h-52 mb-5">
                    <canvas id="serviceChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-4xl font-bold">{{ $summary['orders'] }}</span>
                        <span class="text-sm text-gray-500">orders</span>
                    </div>
                </div>

                <ul class="space-y-3 text-sm">
                    @foreach ($services as $i => $s)
                        <li class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full inline-block"
                                    style="background: {{ $palette[$i % count($palette)] }}"></span>
                                {{ $s['name'] }}
                            </span>
                            <span class="font-semibold">{{ $s['count'] }} • {{ $s['percent'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-400 text-center py-16">No orders in this period.</p>
            @endif
        </div>
    </div>

    {{-- Orders by status --}}
    <h2 class="text-xl mb-4">Orders by Status</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ($statusCounts as $s)
            <div class="bg-white border border-gray-200 rounded-2xl px-5 py-5 flex items-center justify-between">
                <span
                    class="px-3 py-1 rounded-full text-sm {{ $statusBadge[$s['key']] ?? 'bg-gray-100 text-gray-600' }}">{{ $s['label'] }}</span>
                <span class="text-2xl">{{ $s['count'] }}</span>
            </div>
        @endforeach
    </div>

    {{-- Low stock alert --}}
    @if ($lowStock->isNotEmpty())
        @php $first = $lowStock->first(); @endphp
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="flex-1 min-w-[280px] bg-amber-50 rounded-xl px-6 py-4 flex items-start gap-4">
                <i class="ti ti-alert-triangle text-2xl text-amber-700 mt-0.5"></i>
                <div>
                    @if ($lowStock->count() === 1)
                        <p class="font-semibold text-amber-800">{{ ucfirst($first->nama_bahan) }} is below the minimum
                            level</p>
                        <p class="text-sm text-amber-800">
                            There are {{ \App\Models\RawMaterial::formatNumber($first->stok_saat_ini) }}
                            {{ $first->satuan }} of {{ strtolower($first->nama_bahan) }}
                            remaining out of a minimum of {{ \App\Models\RawMaterial::formatNumber($first->stok_minimum) }}
                            {{ $first->satuan }}.
                            Please request a restock to ensure service continues smoothly.
                        </p>
                    @else
                        <p class="font-semibold text-amber-800">{{ $lowStock->count() }} materials are below the minimum
                            level</p>
                        <p class="text-sm text-amber-800">
                            {{ $lowStock->pluck('nama_bahan')->map(fn($n) => ucfirst($n))->join(', ', ' and ') }} are
                            running low.
                            Please request a restock to ensure service continues smoothly.
                        </p>
                    @endif
                </div>
            </div>

            <a href="{{ route('owner.inventory.index') }}"
                class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-6 py-3.5 text-sm font-semibold">
                <i class="ti ti-arrow-right"></i> Check Stock
            </a>
        </div>
    @endif

    {{-- Latest orders --}}
    <h2 class="text-xl mb-4">Latest Orders</h2>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Order code</th>
                    <th class="px-6 py-4 font-medium">Customers</th>
                    <th class="px-6 py-4 font-medium">Services</th>
                    <th class="px-6 py-4 font-medium">Total</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($latest as $o)
                    <tr>
                        <td class="px-6 py-5 whitespace-nowrap">{{ $o->kode_pesanan }}</td>
                        <td class="px-6 py-5">{{ $o->customer->nama }}</td>
                        <td class="px-6 py-5">{{ $o->service->nama_layanan }}</td>
                        <td class="px-6 py-5 whitespace-nowrap">{{ Order::rupiah($o->total_harga) }}</td>
                        <td class="px-6 py-5">
                            <span
                                class="px-3 py-1 rounded-full text-xs whitespace-nowrap {{ $statusBadge[$o->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $o->status_label }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">No orders in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        const trendCanvas = document.getElementById('trendChart');
        const counts = @json($counts);

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: @json($labels),
                datasets: [{
                    label: 'Orders',
                    data: counts,
                    borderColor: '#0b7f6f',
                    backgroundColor: 'rgba(11, 127, 111, 0.10)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0,
                    pointRadius: counts.length > 14 ? 0 : 3,
                    pointBackgroundColor: '#0b7f6f',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        suggestedMax: 2
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });

        const serviceCanvas = document.getElementById('serviceChart');

        if (serviceCanvas) {
            const services = @json($services);

            new Chart(serviceCanvas, {
                type: 'doughnut',
                data: {
                    labels: services.map(s => s.name),
                    datasets: [{
                        data: services.map(s => s.count),
                        backgroundColor: @json($palette),
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '78%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
@endpush
