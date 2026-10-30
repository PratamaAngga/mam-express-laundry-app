@extends('layouts.cashier')

@section('title', $order['code'] . ' - Mam Express')
@section('breadcrumb', 'Order List › ' . $order['code'])

@section('content')
    <a href="{{ route('cashier.orders.index') }}" class="back-link"><x-icon name="arrow-left" :size="14" /> Back to Order List</a>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->has('status'))
        <div class="alert alert-error">{{ $errors->first('status') }}</div>
    @endif

    <div class="banner banner-detail">
        <div class="banner-text">
            <span class="muted small">ORDER CODE</span>
            <strong class="order-code">{{ $order['code'] }}</strong>
            <span class="muted small">Posted by {{ $order['cashier'] }} &bull; {{ $order['posted_at'] }}</span>
        </div>
        <span class="st st-{{ $order['status'] }}">{{ $order['status_label'] }}</span>
    </div>

    <div class="grid grid-success">
        <div class="col-left">
            {{-- ORDER SUMMARY --}}
            <section class="card">
                <h2>Order Summary</h2>

                <div class="customer-block">
                    <strong class="customer-name">{{ $order['customer_name'] }}</strong>
                    <span class="muted small">{{ $order['phone'] }} &bull; {{ $order['customer_orders'] }}
                        {{ $order['customer_orders'] === 1 ? 'order' : 'orders' }} in total</span>
                </div>

                <div class="kv"><span class="muted">Service</span><strong>{{ $order['service_name'] }}</strong></div>
                <div class="kv"><span class="muted">Laundry weight</span><strong>{{ $order['weight'] }} kg</strong></div>
                <div class="kv">
                    <span class="muted">{{ $order['service_name'] }} &middot; {{ $order['weight'] }} kg &times; {{ $order['price_per_kg'] }}</span>
                    <strong>{{ $order['service_total'] }}</strong>
                </div>
                @if ($order['freshener'])
                    <div class="kv"><span class="muted">Premium air freshener</span><strong>{{ $order['freshener_total'] }}</strong></div>
                @endif

                <div class="kv kv-total"><strong>Total payment</strong><strong>{{ $order['total'] }}</strong></div>
                <div class="kv">
                    <span class="muted">Payment status</span>
                    <span class="pill {{ $order['paid'] ? 'pill-mint' : 'pill-amber' }}">{{ $order['paid'] ? 'paid off' : 'unpaid' }}</span>
                </div>

                @if ($order['status'] === 'selesai')
                    <div class="eta-box">
                        <x-icon name="check-circle" :size="16" />
                        <div><strong>Picked up{{ $order['completed_at'] ? ': ' . $order['completed_at'] : '' }}</strong></div>
                    </div>
                @else
                    <div class="eta-box {{ $order['overdue'] ? 'eta-late' : '' }}">
                        <x-icon name="{{ $order['overdue'] ? 'alert-circle' : 'check-circle' }}" :size="16" />
                        <div>
                            <strong>Estimated completion: {{ $order['eta'] }}</strong>
                            @if ($order['overdue'])
                                <br><span class="small">Past the estimated time. The customer may ask about this order.</span>
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            {{-- ACTIONS --}}
            <section class="card">
                <h2>Actions</h2>

                @if ($order['can_complete'])
                    @unless ($order['paid'])
                        <div class="eta-box eta-late">
                            <x-icon name="alert-circle" :size="16" />
                            <div><strong>Collect {{ $order['total'] }} from the customer before handing over the laundry.</strong></div>
                        </div>
                    @endunless
                    <p class="muted small hint">The laundry is packed and waiting at the counter. Mark it as picked up once the customer has collected it.</p>
                    <form method="POST" action="{{ route('cashier.orders.complete', $order['code']) }}"
                        onsubmit="return confirm('Mark {{ $order['code'] }} as picked up? This cannot be undone.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-auto"><x-icon name="check" :size="16" /> Mark as picked up</button>
                    </form>
                @else
                    <p class="muted small hint">{{ $order['blocked_reason'] }}</p>
                    @if ($order['status'] !== 'selesai')
                        <p class="muted small hint">Status moves forward when processing staff scan the order QR label.</p>
                    @endif
                @endif

                <div class="actions actions-secondary">
                    <a href="{{ route('cashier.receipt.index', ['order' => $order['code']]) }}" class="btn-outline"><x-icon name="printer" :size="15" /> Print receipt</a>
                    <a href="{{ $order['wa_url'] }}" target="_blank" rel="noopener" class="btn-outline"><x-icon name="message-circle" :size="15" /> Chat on WhatsApp</a>
                </div>
            </section>
        </div>

        <div class="col-right">
            {{-- STATUS TIMELINE --}}
            <section class="card">
                <h2>Status history</h2>
                <ol class="timeline">
                    @foreach ($timeline as $step)
                        <li class="{{ $step['reached'] ? 'is-reached' : '' }} {{ $step['current'] ? 'is-current' : '' }}">
                            <span class="dot">@if ($step['reached'])<x-icon name="check" :size="11" />@endif</span>
                            <div>
                                <strong>{{ $step['label'] }}</strong>
                                @if ($step['reached'])
                                    <span class="cell-sub">
                                        {{ $step['time'] ?? 'Time not recorded' }}@if ($step['by']) &bull; {{ $step['by'] }}@endif
                                    </span>
                                @else
                                    <span class="cell-sub">Waiting</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
@endsection