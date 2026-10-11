@extends('layouts.cashier')

@section('title', 'New Order - Mam Express')

@section('content')
<div class="page-head">
    <div>
        <h1>New Order</h1>
        <p class="muted">Receive laundry, calculate the price, and send an invoice all in one step.</p>
    </div>
    <span class="status-ok"><x-icon name="check-circle" :size="14" /> The cashier is ready to take orders</span>
</div>

@if (session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif

<form id="order-form" action="{{ route('cashier.orders.store') }}" method="POST" class="grid" data-freshener="{{ $freshenerPrice }}">
    @csrf

    <div class="col-left">
        {{-- CUSTOMER --}}
        <section class="card">
            <div class="card-head">
                <h2>Customer</h2>
                <a href="#" class="link">+ New Customers</a>
            </div>

            <label for="whatsapp">WhatsApp Number</label>
            <div class="field">
                <input type="tel" id="whatsapp" name="whatsapp" inputmode="numeric"
                       placeholder="0812 3456 7890" value="{{ old('whatsapp') }}" autocomplete="off">
                <x-icon name="check-circle" class="field-icon ok" id="wa-check" hidden />
            </div>
            <p id="wa-hint" class="hint"></p>
            @error('whatsapp') <p class="error">{{ $message }}</p> @enderror

            <label for="customer_name">Customer name</label>
            <input type="text" id="customer_name" name="customer_name" placeholder="Customer Name" value="{{ old('customer_name') }}">
            <p id="customer-meta" class="muted small"></p>
            @error('customer_name') <p class="error">{{ $message }}</p> @enderror
        </section>

        {{-- LAUNDRY DETAILS --}}
        <section class="card">
            <h2>Laundry details</h2>
            <p class="muted small">Rates are calculated based on the actual weight of the laundry.</p>

            <label for="weight">Laundry weight (kg)</label>
            <div class="field">
                <input type="text" id="weight" name="weight" inputmode="decimal" placeholder="0" value="{{ old('weight') }}">
                <x-icon name="weight" class="field-icon" />
            </div>
            @error('weight') <p class="error">{{ $message }}</p> @enderror

            <label>Types of Services</label>
            <div class="options">
                @foreach ($services as $service)
                    <label class="option">
                        <input type="radio" name="service" value="{{ $service['code'] }}"
                               data-name="{{ $service['name'] }}"
                               data-price="{{ $service['price'] }}"
                               data-days="{{ $service['days'] }}"
                               {{ old('service', $services[0]['code']) === $service['code'] ? 'checked' : '' }}>
                        <span class="option-top">
                            <span class="dot"><x-icon name="check" :size="10" /></span>
                            <strong>{{ $service['name'] }}</strong>
                        </span>
                        <span>Rp{{ number_format($service['price'], 0, ',', '.') }}/kg</span>
                        <span class="muted small">{{ $service['days'] }} {{ $service['days'] > 1 ? 'days' : 'day' }}</span>
                    </label>
                @endforeach
            </div>

            <label>Additional Services</label>
            <label class="option option-row">
                <input type="checkbox" name="freshener" value="1" id="freshener" {{ old('freshener') ? 'checked' : '' }}>
                <span class="box"><x-icon name="check" :size="12" /></span>
                <span><strong>Premium air freshener</strong><br>
                <span class="muted small">Rp{{ number_format($freshenerPrice, 0, ',', '.') }} / order</span></span>
            </label>

            <label for="notes">Laundry notes (optional)</label>
            <input type="text" id="notes" name="notes" placeholder="Example: Sort the white clothes." value="{{ old('notes') }}">
        </section>
    </div>

    {{-- PRICE SUMMARY --}}
    <aside class="col-right">
        <section class="card">
            <h2>Price Summary</h2>
            <p class="muted small">Updates automatically based on your settings.</p>

            <div class="sum-block">
                <strong id="sum-service">Laundry + Iron</strong>
                <div class="sum-row"><span class="muted" id="sum-calc">0 kg × Rp0</span><span id="sum-service-total">Rp0</span></div>
                <div class="sum-row" id="sum-fresh-row" hidden><span class="muted">Premium air freshener</span><span id="sum-fresh">Rp0</span></div>
            </div>

            <div class="total">
                <span class="muted small">TOTAL PAYMENT</span>
                <strong id="sum-total">Rp0</strong>
                <span class="muted small">No other additional charges</span>
            </div>

            <label for="payment">Payment</label>
            <select id="payment" name="payment">
                <option value="cash_paid">Cash &bull; Paid in Full</option>
                <option value="cash_later">Cash &bull; Pay on Pickup</option>
                <option value="transfer">Transfer</option>
            </select>

            <div class="sum-row spaced"><span class="muted small">Estimated completion</span><strong id="sum-eta">-</strong></div>

            <button type="submit" class="btn"><x-icon name="check" :size="16" /> Save Order</button>
            <p class="muted small center">WhatsApp invoices and QR codes are generated once the order is saved.</p>
        </section>

        <div id="wa-valid" class="valid-box" hidden>
            <x-icon name="check-circle" :size="18" />
            <div>
                <strong>The WhatsApp number is valid</strong><br>
                <span class="small">The invoice will be sent to <span id="wa-valid-number"></span>.</span>
            </div>
        </div>
    </aside>
</form>
@endsection

@push('scripts')
    <script src="{{ asset('js/cashier-new-order.js') }}?v={{ filemtime(public_path('js/cashier-new-order.js')) }}"></script>
@endpush