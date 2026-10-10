@extends('layouts.cashier')

@section('title', 'Order Saved - Mam Express')

@php
    $rp = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');

    // Ilustrasi QR ya teman masihan, JAN LUPA DIGANTI YA
    $n = 21;
    $state = crc32($order['code']);
    $rects = '';
    $corners = [[0, 0], [$n - 7, 0], [0, $n - 7]];
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $dark = null;
            foreach ($corners as [$cx, $cy]) {
                $rx = $x - $cx;
                $ry = $y - $cy;
                if ($rx >= -1 && $rx <= 7 && $ry >= -1 && $ry <= 7) {
                    if ($rx < 0 || $rx > 6 || $ry < 0 || $ry > 6) {
                        $dark = false;
                    } else {
                        $dark = ($rx == 0 || $rx == 6 || $ry == 0 || $ry == 6)
                            || ($rx >= 2 && $rx <= 4 && $ry >= 2 && $ry <= 4);
                    }
                }
            }
            if ($dark === null) {
                $state = ($state * 1103515245 + 12345) & 0x7fffffff;
                $dark = (($state >> 16) & 1) === 1;
            }
            if ($dark) {
                $rects .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1"/>';
            }
        }
    }
@endphp

@section('content')
<div class="no-print">
    <h1>Your order has been successfully saved</h1>
    <p class="muted">The laundry has been registered. The invoice and label are ready for the customer.</p>

    <div class="banner">
        <span class="banner-check"><x-icon name="check" :size="26" /></span>
        <div class="banner-text">
            <span class="muted small">ORDER CODE</span>
            <strong class="order-code">{{ $order['code'] }}</strong>
            <span class="muted small">Posted by {{ $order['cashier'] }} &bull; {{ $order['posted_at'] }}</span>
        </div>
        <span class="pill pill-amber">Accepted</span>
    </div>
</div>

<div class="grid grid-success">
    <div class="col-left no-print">
        {{-- ORDER SUMMARY --}}
        <section class="card">
            <h2>Order Summary</h2>

            <div class="customer-block">
                <strong class="customer-name">{{ $order['customer_name'] }}</strong>
                <span class="muted small">{{ $order['whatsapp'] }}</span>
            </div>

            <div class="kv"><span class="muted">Services</span><strong>{{ $order['service_name'] }}</strong></div>
            <div class="kv"><span class="muted">Laundry weight</span><strong>{{ $order['weight_label'] }} kg</strong></div>
            <div class="kv">
                <span class="muted">{{ $order['service_name'] }} &middot; {{ $order['weight_label'] }} kg &times; {{ $rp($order['price_per_kg']) }}</span>
                <strong>{{ $rp($order['service_total']) }}</strong>
            </div>
            @if ($order['freshener'])
                <div class="kv"><span class="muted">Premium air freshener</span><strong>{{ $rp($order['freshener_total']) }}</strong></div>
            @endif

            <div class="kv kv-total"><strong>Total payment</strong><strong>{{ $rp($order['total']) }}</strong></div>
            <div class="kv">
                <span class="muted">{{ $order['payment_label'] }}</span>
                <span class="pill {{ $order['paid'] ? 'pill-mint' : 'pill-amber' }}">{{ $order['paid'] ? 'paid off' : 'pay on pickup' }}</span>
            </div>

            <div class="eta-box">
                <x-icon name="check-circle" :size="16" />
                <div>
                    <strong>Estimated completion: {{ $order['eta_long'] }}</strong>
                    @if ($order['notes'])
                        <br><span class="small">Note: {{ $order['notes'] }}</span>
                    @endif
                </div>
            </div>
        </section>

        <div class="actions">
            <a href="{{ route('cashier.orders.create') }}" class="btn btn-auto"><x-icon name="plus" :size="16" /> Place a New Order</a>
            <a href="#" class="btn-outline">View Order List</a>
        </div>
    </div>

    <div class="col-right">
        {{-- INVOICE WHATSAPP --}}
        <section class="card no-print">
            <div class="card-head">
                <h2>Invoice WhatsApp</h2>
                <span class="pill pill-mint">Sent</span>
            </div>

            <div class="sent-line">
                <x-icon name="message-circle" :size="16" class="ok-text" />
                <div>
                    <strong>Sent to {{ $order['whatsapp'] }}</strong><br>
                    <span class="muted small">{{ $order['sent_at'] }} &bull; invoice {{ $rp($order['total']) }}</span>
                </div>
            </div>

            <div class="bubble">
                <p>Hello, {{ $order['customer_name'] }}. We have received your order {{ $order['code'] }}.
                    Total: {{ $rp($order['total']) }} ({{ $order['paid'] ? 'paid in full' : 'to be paid on pickup' }}).</p>
                <p class="muted">The laundry is expected to be ready on {{ $order['eta_date_only'] }} at {{ $order['eta_time'] }}.
                    Thank you for choosing Mam Express!</p>
            </div>

            {{-- TODO (backend): kirim ulang invoice lewat WhatsApp --}}
            <button type="button" class="btn-outline btn-block"><x-icon name="send" :size="15" /> Resend Invoice</button>
        </section>

        {{-- QR LABEL --}}
        <section class="card">
            <div class="no-print">
                <h2>Print-ready QR labels</h2>
                <p class="muted small">Label size: 80 &times; 50 mm</p>
            </div>

            <div class="label-card">
                <div class="label-head">
                    <strong>Mam Express</strong>
                    <span class="pill pill-amber">Accepted</span>
                </div>
                <div class="label-body">
                    <svg class="qr" viewBox="0 0 {{ $n }} {{ $n }}" shape-rendering="crispEdges" role="img" aria-label="Ilustrasi QR code">
                        <rect width="{{ $n }}" height="{{ $n }}" fill="#fff"/>
                        <g fill="#000">{!! $rects !!}</g>
                    </svg>
                    <div class="label-info small">
                        <span>{{ $order['code'] }}</span>
                        <strong>{{ $order['customer_name'] }}</strong>
                        <span>{{ $order['weight_label'] }} kg &bull; {{ $order['service_name'] }}</span>
                        <span>Done: {{ $order['eta_label'] }}</span>
                    </div>
                </div>
                <p class="label-note">QR Illustration &bull; Not an operational code</p>
            </div>

            <button type="button" class="btn no-print" onclick="window.print()"><x-icon name="printer" :size="16" /> Print QR Labels</button>
        </section>
    </div>
</div>
@endsection