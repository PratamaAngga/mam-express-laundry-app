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

            {{-- TODO: pemindai QR keranjang dibuat dari mockup berikutnya --}}
            <div class="info-box">
                <x-icon name="scan-line" :size="18" />
                <div>
                    <strong>Basket scanner coming soon</strong><br>
                    <span class="small">The scanner for basket QR codes will appear here.</span>
                </div>
            </div>

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