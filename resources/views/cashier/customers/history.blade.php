@extends('layouts.cashier')

@section('title', $customer['name'] . ' - History - Mam Express')
@section('breadcrumb', 'Customer › ' . $customer['name'])

@section('content')
    <a href="{{ route('cashier.customers.index') }}" class="back-link"><x-icon name="arrow-left" :size="14" /> Back to Customers</a>

    <div class="banner banner-detail">
        <div class="banner-text">
            <span class="muted small">CUSTOMER</span>
            <strong class="order-code">{{ $customer['name'] }}</strong>
            <span class="muted small">{{ $customer['phone'] }}@if ($customer['since']) &bull; Customer since {{ $customer['since'] }}@endif</span>
        </div>
        <a href="{{ $customer['wa_url'] }}" target="_blank" rel="noopener" class="btn-outline"><x-icon name="message-circle" :size="15" /> Chat on WhatsApp</a>
    </div>

    <div class="stats stats-4">
        <div class="stat"><span class="muted">Total orders</span><strong>{{ $summary['orders'] }}</strong></div>
        <div class="stat"><span class="muted">Total spent</span><strong class="stat-money">{{ $summary['spent'] }}</strong></div>
        <div class="stat"><span class="muted">In progress</span><strong>{{ $summary['active'] }}</strong></div>
        <div class="stat"><span class="muted">Unpaid</span><strong class="stat-money">{{ $summary['unpaid'] }}</strong></div>
    </div>

    <div class="toolbar">
        <h2 class="toolbar-title">Order history</h2>
        <div class="toolbar-right">
            <select id="status-filter" class="select-btn" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Order code</th>
                    <th>Service</th>
                    <th>Weight</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="order-rows">
                @foreach ($orders as $order)
                    <tr data-status="{{ $order['status'] }}">
                        <td>
                            <span class="code">{{ $order['code'] }}</span>
                            <span class="cell-sub">{{ $order['date'] }} &bull; {{ $order['time'] }}</span>
                        </td>
                        <td>{{ $order['service'] }}</td>
                        <td>{{ $order['weight'] }} kg</td>
                        <td>
                            {{ $order['total'] }}
                            <span class="cell-sub">{{ $order['paid'] ? 'paid off' : 'unpaid' }}</span>
                        </td>
                        <td><span class="st st-{{ $order['status'] }}">{{ $statuses[$order['status']] }}</span></td>
                        <td>
                            <a href="{{ route('cashier.orders.show', ['code' => $order['code']]) }}" class="action">View details <x-icon name="arrow-right" :size="13" /></a>
                        </td>
                    </tr>
                @endforeach
                <tr id="no-results" @if (count($orders) > 0) hidden @endif>
                    <td colspan="6" class="empty">
                        @if (count($orders) === 0)
                            This customer has no orders yet.
                        @else
                            No orders found.
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-foot">
        <span class="muted small">Showing <span id="shown-count">{{ count($orders) }}</span> of {{ count($orders) }}
            {{ count($orders) === 1 ? 'order' : 'orders' }} &bull; Most recent first</span>
    </div>
@endsection

@push('scripts')
    <script>
        @verbatim
            (function () {
                var status = document.getElementById('status-filter');
                var rows = document.querySelectorAll('#order-rows tr[data-status]');
                var empty = document.getElementById('no-results');
                var shown = document.getElementById('shown-count');

                status.addEventListener('change', function () {
                    var visible = 0;

                    rows.forEach(function (row) {
                        var ok = status.value === '' || row.dataset.status === status.value;
                        row.hidden = !ok;
                        if (ok) visible++;
                    });

                    // Pelanggan tanpa pesanan sama sekali tetap memakai pesan bawaan dari server
                    if (rows.length > 0) {
                        empty.hidden = visible !== 0;
                    }
                    shown.textContent = visible;
                });
            })();
        @endverbatim
    </script>
@endpush