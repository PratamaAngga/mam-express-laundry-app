<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Processing - Mam Express')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        {{-- Font ikon Tabler: dipakai <x-logout-button> --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    <link rel="stylesheet"
        href="{{ asset('css/processing.css') }}?v={{ filemtime(public_path('css/processing.css')) }}">
</head>

<body>
    <div class="app">

        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('processing.home') }}">
                <span class="brand-mark"><x-icon name="washing-machine" :size="20" /></span>
                <span class="brand-text">
                    <strong>Mam Express</strong>
                    <span class="muted small">Laundry &middot; Candi Mendut</span>
                </span>
            </a>

            <div class="workspace">
                <x-icon name="washing-machine" :size="16" />
                <strong>Processing workspace</strong>
            </div>

            <p class="nav-title">PROCESSING</p>
            <nav class="nav">
                <a href="{{ route('processing.home') }}"
                    class="{{ request()->routeIs('processing.home') ? 'active' : '' }}">
                    <x-icon name="home" /> Home
                </a>
                <a href="{{ route('processing.work-session') }}"
                    class="{{ request()->routeIs('processing.work-session') ? 'active' : '' }}">
                    <x-icon name="scan-line" /> Work session
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="shift-card">
                    <span class="shift-label">YOUR SHIFT</span>
                    <strong>{{ $shift['hours'] }}</strong>
                    @if ($shift['started'])
                        <span class="shift-status on"><span class="live-dot"></span> Shift in progress</span>
                    @else
                        <span class="shift-status"><x-icon name="circle" :size="10" /> Not started</span>
                    @endif
                </div>

                <div class="user">
                    <span class="avatar">{{ $staff['initials'] }}</span>
                    <span class="user-info">
                        <strong>{{ $staff['name'] }}</strong><br>
                        <span class="muted small">{{ $staff['role'] }} &middot; {{ $staff['task'] }}</span>
                    </span>
                    <x-logout-button />
                </div>
            </div>
        </aside>

        <div class="main">
            <header class="topbar">
                {{-- Tombol buka sidebar (hanya tampil di layar kecil, seperti di Owner) --}}
                <button type="button" class="menu-btn" aria-label="Open menu" aria-controls="sidebar"
                    aria-expanded="false">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="breadcrumb">
                    <span class="muted crumb-root">Processing /</span>
                    <strong>@yield('breadcrumb', 'Home')</strong>
                </div>
                <div class="topbar-right">
                    {{-- Status shift ringkas: sidebar tersembunyi di HP, jadi status tetap terlihat di topbar --}}
                    <span class="shift-pill {{ $shift['started'] ? 'on' : '' }}">
                        <span class="live-dot"></span>
                        {{ $shift['started'] ? 'On shift' : 'Off shift' }}
                    </span>
                    <span class="muted small topbar-date">{{ now()->format('l, j F Y') }}</span>
                    <span class="online"><span class="live-dot"></span> All systems online</span>
                    <a href="#" class="bell" aria-label="Notifications"><x-icon name="bell"
                            :size="16" /></a>
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </div>
        <div class="sidebar-backdrop" aria-hidden="true"></div>
    </div>

    <script src="{{ asset('js/processing-menu.js') }}?v={{ filemtime(public_path('js/processing-menu.js')) }}"></script>
    @stack('scripts')
</body>

</html>