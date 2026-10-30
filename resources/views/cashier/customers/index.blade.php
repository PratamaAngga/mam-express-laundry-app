@extends('layouts.cashier')

@section('title', 'Customers - Mam Express')
@section('breadcrumb', 'Customer')

@section('content')
    @php $panelOpen = $errors->any(); @endphp

    <div class="page-head">
        <div>
            <h1>Customer</h1>
            <p class="muted">One WhatsApp number for an easy-to-find laundry history.</p>
        </div>
        <button type="button" class="btn btn-auto" id="open-panel"><x-icon name="plus" :size="16" /> New Customer</button>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="cust-layout {{ $panelOpen ? 'is-open' : '' }}" id="cust-layout">

        {{-- DAFTAR PELANGGAN --}}
        <div class="cust-list">
            <div class="list-top">
                <div class="search">
                    <x-icon name="search" :size="16" class="search-icon" />
                    <input type="search" id="customer-search" placeholder="Search by name or WhatsApp number"
                        autocomplete="off">
                </div>
                <span class="muted small"><span id="top-count">{{ count($customers) }}</span> customers</span>
            </div>

            <div class="table-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>WhatsApp</th>
                            <th>Last visit</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="customer-rows">
                        @foreach ($customers as $customer)
                            <tr data-search="{{ strtolower($customer['name']) }} {{ $customer['phone'] }} {{ preg_replace('/\D/', '', $customer['phone']) }}"
                                data-digits="{{ preg_replace('/\D/', '', $customer['phone']) }}">
                                <td>
                                    <strong>{{ $customer['name'] }}</strong>
                                    <span class="cell-sub">{{ $customer['orders'] }}
                                        {{ $customer['orders'] === 1 ? 'order' : 'orders' }}</span>
                                </td>
                                <td>{{ $customer['phone'] }}</td>
                                <td>{{ $customer['last_visit']->format('j M Y') }}</td>
                                <td>
                                    <a href="{{ route('cashier.customers.history', ['customer' => $customer['id']]) }}" class="action">History <x-icon name="arrow-right" :size="13" /></a>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="no-results" hidden>
                            <td colspan="4" class="empty">No customers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-foot">
                <span class="muted small">Showing <span id="shown-count">{{ count($customers) }}</span> of
                    {{ count($customers) }} customers &bull; Sorted by most recent visit</span>
            </div>
        </div>

        {{-- FORM PELANGGAN BARU --}}
        <aside class="side-panel card" id="customer-panel" {{ $panelOpen ? '' : 'hidden' }}>
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">+ New Customer</h2>
                    <p class="muted small">Fill out the form before accepting the laundry.</p>
                </div>
                <button type="button" class="icon-btn" id="close-panel" aria-label="Close"><x-icon name="x"
                        :size="16" /></button>
            </div>

            <form id="customer-form" action="{{ route('cashier.customers.store') }}" method="POST"
                data-existing="{{ json_encode($existing) }}" novalidate>
                @csrf

                <label for="full_name">Full name *</label>
                <input type="text" id="full_name" name="full_name" placeholder="Full name" value="{{ old('full_name') }}">
                @error('full_name')
                <p class="error">{{ $message }}</p> @enderror

                <label for="cust_whatsapp">WhatsApp number *</label>
                <div class="field">
                    <input type="tel" id="cust_whatsapp" name="whatsapp" inputmode="numeric" placeholder="0812 3456 7890"
                        value="{{ old('whatsapp') }}" autocomplete="off">
                    <x-icon name="check-circle" class="field-icon ok" id="cust-check" hidden />
                </div>
                <p id="cust-hint" class="hint"></p>
                @error('whatsapp')
                <p class="error">{{ $message }}</p> @enderror

                <label for="notes">Customer notes (optional)</label>
                <input type="text" id="notes" name="notes" placeholder="Add a note" value="{{ old('notes') }}">

                <div class="valid-box info-gap">
                    <x-icon name="check-circle" :size="18" />
                    <div>
                        <strong>Invoices via WhatsApp</strong><br>
                        <span class="small">Make sure the number is active and has been confirmed by the customer.</span>
                    </div>
                </div>

                <div class="panel-actions">
                    <button type="submit" class="btn"><x-icon name="check" :size="16" /> Save Customer</button>
                    <button type="button" class="btn-outline btn-block" id="cancel-panel">Cancel</button>
                </div>

                <p class="muted small">* Required field. New customers will appear automatically on the order form.</p>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        @verbatim
            (function () {
                var layout = document.getElementById('cust-layout');
                var panel = document.getElementById('customer-panel');
                var form = document.getElementById('customer-form');
                var openBtn = document.getElementById('open-panel');
                var closeBtn = document.getElementById('close-panel');
                var cancelBtn = document.getElementById('cancel-panel');

                var nameInput = document.getElementById('full_name');
                var wa = document.getElementById('cust_whatsapp');
                var notes = document.getElementById('notes');
                var check = document.getElementById('cust-check');
                var hint = document.getElementById('cust-hint');

                var existing = JSON.parse(form.dataset.existing || '[]');

                function normalize(v) {
                    var d = v.replace(/\D/g, '');
                    return d.indexOf('62') === 0 ? '0' + d.slice(2) : d;
                }

                // ---- Panel buka / tutup ----
                function openPanel() {
                    panel.hidden = false;
                    layout.classList.add('is-open');
                    nameInput.focus();
                }

                function closePanel() {
                    panel.hidden = true;
                    layout.classList.remove('is-open');
                    nameInput.value = '';
                    wa.value = '';
                    notes.value = '';
                    form.querySelectorAll('.error').forEach(function (el) { el.remove(); });
                    validate();
                }

                openBtn.addEventListener('click', openPanel);
                closeBtn.addEventListener('click', closePanel);
                cancelBtn.addEventListener('click', closePanel);

                // ---- Cek nomor WhatsApp ----
                function validate() {
                    var d = normalize(wa.value);
                    wa.classList.remove('is-valid', 'is-invalid');
                    check.hidden = true;
                    hint.textContent = '';
                    hint.className = 'hint';

                    if (d.length < 10) return;

                    if (!/^08\d{8,11}$/.test(d)) {
                        hint.textContent = 'Enter a valid Indonesian WhatsApp number.';
                        return;
                    }
                    if (existing.indexOf(d) !== -1) {
                        wa.classList.add('is-invalid');
                        hint.textContent = 'This number is already registered.';
                        hint.className = 'hint err';
                        return;
                    }
                    wa.classList.add('is-valid');
                    check.hidden = false;
                    hint.textContent = 'The number is not registered. Ready to save.';
                    hint.className = 'hint ok';
                }

                wa.addEventListener('input', validate);
                validate();

                // ---- Pencarian pelanggan ----
                var search = document.getElementById('customer-search');
                var rows = document.querySelectorAll('#customer-rows tr[data-search]');
                var empty = document.getElementById('no-results');
                var shown = document.getElementById('shown-count');
                var topCount = document.getElementById('top-count');

                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase();
                    var qDigits = q.replace(/\D/g, '');
                    var visible = 0;

                    rows.forEach(function (row) {
                        var ok = q === '' ||
                            row.dataset.search.indexOf(q) !== -1 ||
                            (qDigits !== '' && row.dataset.digits.indexOf(qDigits) !== -1);
                        row.hidden = !ok;
                        if (ok) visible++;
                    });

                    empty.hidden = visible !== 0;
                    shown.textContent = visible;
                    topCount.textContent = visible;
                });
            })();
        @endverbatim
    </script>
@endpush