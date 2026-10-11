<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mam Express')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/cashier.css') }}?v={{ filemtime(public_path('css/cashier.css')) }}">
    @stack('styles')
</head>

<body>
    <div class="app">

        <aside class="sidebar">
            <a class="brand" href="{{ route('cashier.orders.create') }}">
                <span class="brand-mark"><x-icon name="washing-machine" :size="20" /></span>
                <span class="brand-name">Mam Express</span>
            </a>

            <div class="location">
                <span class="muted small">Location</span>
                <strong>Candi Panggung, Malang</strong>
            </div>

            <p class="nav-title">Cashier's counter</p>
            <nav class="nav">
                <a href="{{ route('cashier.orders.create') }}"
                    class="{{ request()->routeIs('cashier.orders.create', 'cashier.orders.success') ? 'active' : '' }}">
                    <x-icon name="plus-circle" /> New Order
                </a>
                <a href="{{ route('cashier.orders.index') }}"
                    class="{{ request()->routeIs('cashier.orders.index') ? 'active' : '' }}">
                    <x-icon name="clipboard" /> Order List
                </a>
                <a href="#"><x-icon name="users" /> Customers</a>
                <a href="#"><x-icon name="printer" /> Print Receipt</a>
            </nav>

            <div class="sidebar-bottom">
                <a href="#" class="help"><x-icon name="help-circle" :size="16" /> Help Center</a>
                <div class="user">
                    <span class="avatar">K</span>
                    <span class="user-info"><strong>Kartiko</strong><br><span class="muted small">Cashier</span></span>
                    <a href="#" class="logout" aria-label="Log out"><x-icon name="log-out" :size="16" /></a>
                </div>
            </div>
        </aside>

        <div class="main">
            <header class="topbar">
                <div class="breadcrumb">
                    <span class="muted">Cashier</span> <span class="muted">&rsaquo;</span>
                    <strong>@yield('breadcrumb', 'New Order')</strong>
                </div>
                <div class="topbar-right">
                    <span class="muted small">{{ now()->format('l, F j, Y') }}</span>
                    <a href="#" class="bell" aria-label="Notifications"><x-icon name="bell" :size="16" /></a>
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>