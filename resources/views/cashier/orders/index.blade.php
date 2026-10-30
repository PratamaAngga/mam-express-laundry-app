@extends('layouts.cashier')

@section('title', 'Order List - Mam Express')
@section('breadcrumb', 'Order List')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/cashier.css') }}?v={{ filemtime(public_path('css/cashier.css')) }}">
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Order List</h1>
            <p class="muted">Find orders and respond to customer questions quickly.</p>
        </div>
        <a href="{{ route('cashier.orders.create') }}" class="btn btn-auto"><x-icon name="plus" :size="16" /> New Order</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="stats">
        <div class="stat"><span class="muted">All orders</span><strong>{{ $counts['all'] }}</strong></div>
        <div class="stat"><span class="muted">{{ $statuses['antre_cuci'] }}</span><strong>{{ $counts['queued'] }}</strong></div>
        <div class="stat"><span class="muted">{{ $statuses['diproses'] }}</span><strong>{{ $counts['processing'] }}</strong></div>
        <div class="stat"><span class="muted">{{ $statuses['dikemas_siap_diambil'] }}</span><strong>{{ $counts['ready'] }}</strong></div>
        <div class="stat"><span class="muted">{{ $statuses['selesai'] }}</span><strong>{{ $counts['done'] }}</strong></div>
    </div>

    <div class="toolbar">
        <div class="search">
            <x-icon name="search" :size="16" class="search-icon" />
            <input type="search" id="order-search" placeholder="Search by code or customer name" autocomplete="off">
        </div>

        <div class="toolbar-right">
            <select id="status-filter" class="select-btn" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            {{-- TODO (backend): filter rentang tanggal --}}
            <button type="button" class="btn-outline date-btn"><x-icon name="calendar" :size="15" /> 3–9 Oct 2026</button>
        </div>
    </div>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Order code</th>
                    <th>Customer</th>
                    <th>Weight</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="order-rows">
                @foreach ($orders as $order)
                    <tr data-status="{{ $order['status'] }}"
                        data-search="{{ strtolower($order['code'] . ' ' . $order['name']) }}">
                        <td>
                            <span class="code">{{ $order['code'] }}</span>
                            <span class="cell-sub">{{ $order['date'] }} &bull; {{ $order['time'] }}</span>
                        </td>
                        <td>
                            <strong>{{ $order['name'] }}</strong>
                            <span class="cell-sub">{{ $order['phone'] }}</span>
                        </td>
                        <td>{{ $order['weight'] }} kg</td>
                        <td>{{ $order['service'] }}</td>
                        <td><span class="st st-{{ $order['status'] }}">{{ $statuses[$order['status']] }}</span></td>
                        <td>
                            @if ($order['status'] === \App\Models\Order::STATUS_PACKED)
                                {{-- Cucian siap diambil: kasir menyerahkannya ke pelanggan --}}
                                <form method="POST" action="{{ route('cashier.orders.complete', ['code' => $order['code']]) }}"
                                    class="inline-form" onsubmit="return confirm('Mark this order as picked up by the customer?')">
                                    @csrf
                                    <input type="hidden" name="from" value="{{ $order['status'] }}">
                                    <button type="submit" class="btn-outline btn-sm">Mark as picked up</button>
                                </form>
                            @else
                                {{-- TODO: arahkan ke halaman detail order --}}
                                <a href="#" class="action">View details <x-icon name="arrow-right" :size="13" /></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr id="no-results" hidden>
                    <td colspan="6" class="empty">No orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-foot">
        <span class="muted small">Showing <span id="shown-count">{{ count($orders) }}</span> of {{ count($orders) }}
            orders</span>
        {{-- TODO (backend): pagination sungguhan --}}
        <div class="pager">
            <button type="button" class="btn-outline btn-sm" disabled>Previous</button>
            <span class="page-current">1</span>
            <button type="button" class="btn-outline btn-sm" disabled>Next</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        @verbatim
            // Filter daftar order langsung di browser (data contoh).
            // Nanti bisa diganti filter dari backend lewat query string.
            (function () {
                var search = document.getElementById('order-search');
                var status = document.getElementById('status-filter');
                var rows = document.querySelectorAll('#order-rows tr[data-status]');
                var empty = document.getElementById('no-results');
                var shown = document.getElementById('shown-count');

                function apply() {
                    var q = search.value.trim().toLowerCase();
                    var s = status.value;
                    var visible = 0;

                    rows.forEach(function (row) {
                        var ok = (s === '' || row.dataset.status === s) &&
                            (q === '' || row.dataset.search.indexOf(q) !== -1);
                        row.hidden = !ok;
                        if (ok) visible++;
                    });

                    empty.hidden = visible !== 0;
                    shown.textContent = visible;
                }

                search.addEventListener('input', apply);
                status.addEventListener('change', apply);
            })();
        @endverbatim
    </script>
@endpush