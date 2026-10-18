@extends('layouts.processing')

@section('title', 'Work Session - Mam Express')
@section('breadcrumb', 'Work session')

@section('content')
    <div class="page-head">
        <div>
            <h1>Work session</h1>
            <p class="muted">Scan and confirm each basket as you work.</p>
        </div>
        <span class="muted small">{{ $staff['task'] }} &middot; {{ $staff['first_name'] }}</span>
    </div>

    <section class="hero-card">
        <span class="hero-icon"><x-icon name="scan-line" :size="22" /></span>

        @if ($shift['started'])
            <h2>Shift in progress</h2>
            <p class="muted">Started at {{ $shift['started_at'] }} &middot; Assigned task: {{ $staff['task'] }} &rarr; Status:
                {{ $staff['status'] }}</p>

            {{-- Pemindai kamera (dipakai di HP pribadi petugas) --}}
            <div id="scanner" class="scanner" data-order-base="{{ url('/processing/orders') }}">
                <div id="scanner-view" class="scanner-view"></div>
                <p id="scanner-msg" class="small muted" role="status">Scan the QR label on the basket to open the order.</p>
                <div class="scanner-actions">
                    <button type="button" id="scanner-start" class="btn-primary"><x-icon name="scan-line" :size="16" /> Start camera</button>
                    <button type="button" id="scanner-stop" class="btn-outline" hidden>Stop camera</button>
                </div>
            </div>

            {{-- Cadangan kalau QR gagal terbaca --}}
            <form method="POST" action="{{ route('processing.scan') }}" class="scan-form">
                @csrf
                <input type="text" name="code" class="field-input" placeholder="Or type the order code, e.g. BNG-261017-0001"
                    value="{{ old('code') }}" autocomplete="off" required>
                <button type="submit" class="btn-outline">Open order</button>
            </form>
            @error('code')
                <p class="small field-error">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('processing.shift.finish') }}">
                @csrf
                <button type="submit" class="btn-outline">Finish Shift</button>
            </form>
        @else
            <h2>Your shift has not started yet</h2>
            <p class="muted">Start your shift from the Home page before scanning baskets.</p>
            <a href="{{ route('processing.home') }}" class="btn-primary"><x-icon name="arrow-right" :size="16" /> Go to Home</a>
        @endif
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
    <script src="{{ asset('js/processing-scanner.js') }}?v={{ filemtime(public_path('js/processing-scanner.js')) }}"></script>
@endpush
