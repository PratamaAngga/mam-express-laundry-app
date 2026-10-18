@extends('layouts.processing')

@section('title', 'Order ' . $order->kode_pesanan . ' - Mam Express')
@section('breadcrumb', 'Order')

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $order->kode_pesanan }}</h1>
            <p class="muted">Review the order, then confirm the status update.</p>
        </div>
        <span class="status-pill">{{ $labels[$order->status] ?? $order->status }}</span>
    </div>

    <section class="hero-card">
        @if (session('status'))
            <div class="info-box ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="info-box warn">{{ session('error') }}</div>
        @endif

        <dl class="order-grid">
            <div><dt>Customer</dt><dd>{{ $order->customer->nama }}</dd></div>
            <div><dt>Service</dt><dd>{{ $order->service->nama_layanan }}</dd></div>
            <div><dt>Weight</dt><dd>{{ \App\Models\Order::kg($order->berat_kg) }} kg</dd></div>
            <div><dt>Extras</dt><dd>{{ $order->additionalServices->pluck('nama_tambahan')->implode(', ') ?: '-' }}</dd></div>
            <div><dt>Received</dt><dd>{{ $order->waktu_pesanan->format('F j, Y, H.i') }}</dd></div>
            <div><dt>Estimated done</dt><dd>{{ $order->waktu_pesanan->copy()->addDays($order->service->estimasi_hari)->format('F j, Y, H.i') }}</dd></div>
        </dl>

        @if ($canAdvance && $next)
            <form method="POST" action="{{ route('processing.order.advance', ['code' => $order->kode_pesanan]) }}">
                @csrf
                <input type="hidden" name="from" value="{{ $order->status }}">
                <button type="submit" class="btn-primary">{{ $actionLabel }} &rarr; {{ $labels[$next] }}</button>
            </form>
        @else
            <div class="info-box">
                @if ($order->status === \App\Models\Order::STATUS_PACKED)
                    This order is packed and waiting at the counter. The cashier marks it as done when the customer picks it up.
                @elseif ($order->status === \App\Models\Order::STATUS_DONE)
                    This order is already done.
                @else
                    You cannot update this order.
                @endif
            </div>
        @endif

        @if ($order->histories->isNotEmpty())
            <h3 class="hero-sub">Status history</h3>
            <ul class="history-list">
                @foreach ($order->histories as $h)
                    <li>
                        <span>{{ $labels[$h->status_baru] ?? $h->status_baru }}</span>
                        <span class="muted small">{{ $h->waktu_perubahan->format('M j, H.i') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <a href="{{ route('processing.work-session') }}" class="btn-outline hero-note">Back to work session</a>
    </section>
@endsection
