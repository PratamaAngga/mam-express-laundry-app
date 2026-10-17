<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Owner') — Mam Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @stack('head')
</head>

<body class="bg-[#f4f8f7] text-[#12332d]">
    @php
        // Menu items for pages that don't exist yet (other branches) fall back to '#'
$link = fn(string $name) => \Illuminate\Support\Facades\Route::has($name) ? route($name) : '#';
$menu = [
    [
        'href' => $link('owner.dashboard'),
        'icon' => 'ti-layout-dashboard',
        'label' => 'Dashboard',
        'match' => 'owner.dashboard',
    ],
    [
        'href' => $link('owner.inventory.index'),
        'icon' => 'ti-package',
        'label' => 'Raw Material Inventory',
        'match' => 'owner.inventory.*',
    ],
    [
        'href' => $link('owner.restock.index'),
        'icon' => 'ti-truck-delivery',
        'label' => 'Restock Supplier',
        'match' => 'owner.restock.*',
    ],
    [
        'href' => $link('owner.reports.index'),
        'icon' => 'ti-chart-bar',
        'label' => 'Report',
        'match' => 'owner.reports.*',
    ],
    [
        'href' => $link('owner.employees.index'),
        'icon' => 'ti-users',
        'label' => 'Employee',
        'match' => 'owner.employees.*',
    ],
    [
        'href' => $link('owner.pricing.index'),
        'icon' => 'ti-adjustments-horizontal',
        'label' => 'Service Pricing',
        'match' => 'owner.pricing.*',
            ],
        ];
    @endphp

    <input type="checkbox" id="sidebar-toggle" class="peer hidden">

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 flex flex-col -translate-x-full peer-checked:translate-x-0 lg:translate-x-0 transition-transform">
        <div class="px-5 pt-6 pb-4 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-teal-700 text-white flex items-center justify-center text-xl">
                <i class="ti ti-wash-machine"></i>
            </div>
            <span class="text-xl font-medium text-[#12332d]">Mam Express</span>
        </div>

        <div class="mx-4 mb-5 bg-[#f4f8f7] rounded-xl px-4 py-3">
            <p class="text-xs text-gray-500">Location</p>
            <p class="text-sm font-semibold text-[#12332d]">Candi Panggung, Malang</p>
        </div>

        <p class="px-6 mb-2 text-[11px] font-semibold tracking-wide text-gray-500 uppercase">Owner's Counter</p>
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
            @foreach ($menu as $m)
                <a href="{{ $m['href'] }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs($m['match']) ? 'bg-teal-50 text-teal-800 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ti {{ $m['icon'] }} text-lg"></i> {{ $m['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="px-6 py-3 text-sm text-gray-500 flex items-center gap-3">
            <i class="ti ti-help-circle text-lg"></i> Help Center
        </div>
        <div class="mx-4 border-t border-gray-100"></div>
        <div class="px-5 py-4 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-teal-50 text-teal-700 font-semibold flex items-center justify-center">
                {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold truncate">{{ auth()->user()->nama }}</p>
                <p class="text-xs text-gray-500">Owner / Admin</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button title="Log out" class="text-gray-500 hover:text-gray-800 text-lg">
                    <i class="ti ti-logout"></i>
                </button>
            </form>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <label for="sidebar-toggle" class="fixed inset-0 z-20 bg-black/30 hidden peer-checked:block lg:hidden"></label>

    {{-- Main content --}}
    <div class="lg:ml-64 min-h-screen">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-10 h-16 flex items-center gap-3">
            <label for="sidebar-toggle" class="lg:hidden cursor-pointer text-2xl text-gray-600">
                <i class="ti ti-menu-2"></i>
            </label>

            <nav class="flex items-center gap-2 text-sm">
                <span class="text-gray-500">Owner</span>
                <i class="ti ti-chevron-right text-gray-400 text-xs"></i>
                <span class="font-medium">@yield('title')</span>
            </nav>

            <div class="ml-auto flex items-center gap-5 text-sm text-gray-500">
                <span class="hidden sm:inline">{{ now()->locale('en')->isoFormat('dddd, MMMM D, YYYY') }}</span>
                <button class="text-xl text-gray-500 hover:text-gray-800"><i class="ti ti-bell"></i></button>
            </div>
        </header>

        <main class="px-4 sm:px-10 py-8">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[#12332d]">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="text-gray-500 mt-1">@yield('subheading')</p>
                    @endif
                </div>
                @yield('actions')
            </div>

            @if (session('status'))
                <div class="mb-4 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>
