@extends('layouts.processing')

@section('title', 'Processing Home - Mam Express')
@section('breadcrumb', 'Home')

@section('content')
    <div class="page-head">
        <div>
            <h1>Processing home</h1>
            <p class="muted">Start your shift once, then scan and confirm each basket as you work.</p>
        </div>
        <span class="muted small">{{ $staff['task'] }} &middot; {{ $staff['first_name'] }}</span>
    </div>

    <section class="hero-card">
        <span class="hero-icon"><x-icon name="washing-machine" :size="22" /></span>

        @if ($shift['started'])
            <h2>Shift in progress, {{ $staff['first_name'] }}</h2>
            <p class="muted">You started at {{ $shift['started_at'] }}. Open your work session to scan the next basket.</p>
        @else
            <h2>Ready to start, {{ $staff['first_name'] }}?</h2>
            <p class="muted">You're signed in as {{ $staff['role'] }} staff. Your shift has not started yet.</p>
        @endif

        <div class="info-box">
            <x-icon name="lock" :size="18" />
            <div>
                <strong>Status flow: {{ $staff['flow'] }}</strong><br>
                <span class="small">Each scan moves the order one step forward, based on its current status in the
                    system. No status selection needed.</span>
            </div>
        </div>

        @if ($shift['started'])
            <a href="{{ route('processing.work-session') }}" class="btn-primary">
                <x-icon name="arrow-right" :size="16" /> Open Work Session
            </a>
        @else
            <h3 class="hero-sub">One start. A simple cycle for every basket.</h3>
            <p class="muted">Scan the basket QR, review the order, then confirm the status update. After it completes, the
                scanner is ready for your next basket.</p>

            <form method="POST" action="{{ route('processing.shift.start') }}">
                @csrf
                <button type="submit" class="btn-primary"><x-icon name="arrow-right" :size="16" /> Start Work</button>
            </form>

            <p class="muted small hero-note">Start Work begins your shift. Choose Finish Shift manually when your work is done.
            </p>
        @endif
    </section>
@endsection