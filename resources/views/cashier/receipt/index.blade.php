@extends('layouts.cashier')

@section('title', 'Print Receipt - Mam Express')
@section('breadcrumb', 'Print Receipt')

@section('content')
    <div class="page-head no-print">
        <div>
            <h1>Print Receipt</h1>
            <p class="muted">Preview invoices and laundry labels before printing.</p>
        </div>
        <button type="button" class="btn btn-auto" id="print-all"><x-icon name="printer" :size="16" /> Print All</button>
    </div>

    <div id="receipt-root" class="no-print" data-orders="{{ json_encode($orders) }}">
        <label for="order-select" class="select-label">Select an order</label>
        <div class="select-row">
            <select id="order-select" class="select-wide">
                @foreach ($orders as $order)
                    <option value="{{ $order['code'] }}" {{ $order['code'] === $selected['code'] ? 'selected' : '' }}>
                        {{ $order['option_label'] }}</option>
                @endforeach
            </select>
            <span class="muted small" data-f="received">{{ $selected['received'] }}</span>
        </div>
    </div>

    <div class="receipt-grid no-print">

        {{-- INVOICE PREVIEW --}}
        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Invoice Preview</h2>
                    <p class="muted small">80 mm thermal paper</p>
                </div>
                <span class="pill {{ $selected['paid'] ? 'pill-mint' : 'pill-amber' }}" data-f="paid_label"
                    data-paid-pill>{{ $selected['paid_label'] }}</span>
            </div>

            <div class="paper" id="invoice-paper">
                <div class="paper-brand">
                    <strong>Mam Express</strong>
                    <span>Laundry Candi Panggung</span>
                    <span>Jl. Candi Panggung Bar. No.61, Mojolangu</span>
                    <span>WhatsApp: 0856 0048 9358</span>
                </div>

                <div class="paper-row"><span class="muted">Order No.</span><strong
                        data-f="code">{{ $selected['code'] }}</strong></div>
                <div class="paper-row"><span class="muted">Date</span><strong
                        data-f="date_short">{{ $selected['date_short'] }}</strong></div>
                <div class="paper-row"><span class="muted">Customer</span><strong
                        data-f="name">{{ $selected['name'] }}</strong></div>
                <div class="paper-row"><span class="muted">WhatsApp</span><strong
                        data-f="phone">{{ $selected['phone'] }}</strong></div>

                <hr class="paper-rule">

                <div class="paper-row">
                    <div>
                        <strong data-f="service_name">{{ $selected['service_name'] }}</strong><br>
                        <span class="paper-item-sub" data-f="calc">{{ $selected['calc'] }}</span>
                    </div>
                    <span data-f="service_total">{{ $selected['service_total'] }}</span>
                </div>
                <div class="paper-row" data-show="freshener" {{ $selected['freshener'] ? '' : 'hidden' }}>
                    <span class="muted">Premium air freshener</span>
                    <strong data-f="freshener_total">{{ $selected['freshener_total'] }}</strong>
                </div>

                <hr class="paper-rule">

                <div class="paper-row strong"><span>Total</span><span data-f="total">{{ $selected['total'] }}</span></div>
                <div class="paper-row"><span class="muted">Cash payment</span><strong
                        data-f="paid_amount">{{ $selected['paid_amount'] }}</strong></div>
                <div class="paper-row"><span class="muted">Remaining payment</span><strong
                        data-f="remaining">{{ $selected['remaining'] }}</strong></div>

                <hr class="paper-rule">

                <p class="paper-eta">Estimated completion: <span data-f="eta">{{ $selected['eta'] }}</span></p>
                <p class="paper-foot">Please keep your receipt for when you pick up your laundry. Thank you for choosing Mam
                    Express.</p>
            </div>

            <button type="button" class="btn-outline btn-block" id="print-invoice"><x-icon name="printer" :size="15" />
                Print Invoice</button>
        </section>

        <div class="col-right">
            {{-- QR CODE PREVIEW --}}
            <section class="card">
                <h2>QR Code Preview</h2>
                <p class="muted small">Label 80 &times; 50 mm &bull; 1 label per order</p>

                <div class="label-card" id="label-card">
                    <div class="label-head">
                        <strong>Mam Express</strong>
                        <span class="st st-{{ $selected['status_key'] }}" data-f="status_label"
                            data-status-pill>{{ $selected['status_label'] }}</span>
                    </div>
                    <div class="label-body">
                        <svg class="qr" viewBox="0 0 {{ $selected['qr_size'] }} {{ $selected['qr_size'] }}" shape-rendering="crispEdges"
                            role="img" aria-label="QR code for this order">
                            <rect width="100%" height="100%" fill="#fff" />
                            <g fill="#000" class="qr-rects">{!! $selected['qr'] !!}</g>
                        </svg>
                        <div class="label-info small">
                            <span data-f="code">{{ $selected['code'] }}</span>
                            <strong data-f="name">{{ $selected['name'] }}</strong>
                            <span data-f="weight_service">{{ $selected['weight_service'] }}</span>
                            <span data-f="completed">{{ $selected['completed'] }}</span>
                        </div>
                    </div>
                    <p class="label-note">Scan to open this order &bull; For processing staff</p>
                </div>

                <button type="button" class="btn" id="print-label"><x-icon name="printer" :size="16" /> Print QR
                    Labels</button>
            </section>

            {{-- PRINT SETTINGS --}}
            <section class="card">
                <h2>Print Settings</h2>

                <label for="printer">Printer</label>
                <select id="printer">
                    <option>Cash Register Printer &bull; 80 mm Thermal</option>
                    <option>Label Printer &bull; 80 &times; 50 mm</option>
                </select>

                <div class="settings-grid">
                    <div>
                        <label for="copies-invoice">Copy invoice</label>
                        <select id="copies-invoice">
                            @foreach ([1, 2, 3, 4, 5] as $n)
                            <option value="{{ $n }}">{{ $n }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="copies-label">Copy label</label>
                        <select id="copies-label">
                            @foreach ([1, 2, 3, 4, 5] as $n)
                            <option value="{{ $n }}">{{ $n }}</option> @endforeach
                        </select>
                    </div>
                </div>

                <div class="valid-box info-gap">
                    <x-icon name="check-circle" :size="18" />
                    <div>
                        <strong>The WhatsApp invoice has been sent</strong><br>
                        <span class="small" data-f="sent_text">{{ $selected['sent_text'] }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Area cetak: diisi lewat JavaScript saat tombol Print ditekan --}}
    <div id="print-area" aria-hidden="true"></div>
@endsection

@push('scripts')
    <script>
        @verbatim
            (function () {
                var root = document.getElementById('receipt-root');
                var orders = JSON.parse(root.dataset.orders);
                var byCode = {};
                orders.forEach(function (o) { byCode[o.code] = o; });

                var select = document.getElementById('order-select');

                // Isi ulang pratinjau sesuai order yang dipilih
                function render(o) {
                    document.querySelectorAll('[data-f]').forEach(function (el) {
                        var key = el.dataset.f;
                        if (key in o) el.textContent = o[key];
                    });
                    document.querySelectorAll('[data-show]').forEach(function (el) {
                        el.hidden = !o[el.dataset.show];
                    });
                    document.querySelectorAll('[data-paid-pill]').forEach(function (el) {
                        el.className = 'pill ' + (o.paid ? 'pill-mint' : 'pill-amber');
                    });
                    document.querySelectorAll('[data-status-pill]').forEach(function (el) {
                        el.className = 'st st-' + o.status_key;
                    });
                    document.querySelectorAll('.qr-rects').forEach(function (el) {
                        el.innerHTML = o.qr;
                        el.closest('svg').setAttribute('viewBox', '0 0 ' + o.qr_size + ' ' + o.qr_size);
                    });
                }

                select.addEventListener('change', function () {
                    render(byCode[select.value]);
                });

                // Cetak: salin invoice / label sebanyak jumlah copy ke area cetak
                function printNow(mode) {
                    var area = document.getElementById('print-area');
                    var copiesInvoice = parseInt(document.getElementById('copies-invoice').value, 10) || 1;
                    var copiesLabel = parseInt(document.getElementById('copies-label').value, 10) || 1;
                    area.innerHTML = '';

                    function add(source, times) {
                        for (var i = 0; i < times; i++) {
                            var copy = source.cloneNode(true);
                            copy.removeAttribute('id');
                            area.appendChild(copy);
                        }
                    }

                    if (mode !== 'label') add(document.getElementById('invoice-paper'), copiesInvoice);
                    if (mode !== 'invoice') add(document.getElementById('label-card'), copiesLabel);

                    window.print();
                }

                window.addEventListener('afterprint', function () {
                    document.getElementById('print-area').innerHTML = '';
                });

                document.getElementById('print-invoice').addEventListener('click', function () { printNow('invoice'); });
                document.getElementById('print-label').addEventListener('click', function () { printNow('label'); });
                document.getElementById('print-all').addEventListener('click', function () { printNow('all'); });
            })();
        @endverbatim
    </script>
@endpush