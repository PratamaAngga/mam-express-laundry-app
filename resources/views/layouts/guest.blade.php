<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mam Express')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
</head>

<body class="min-h-screen bg-white">
    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:flex flex-col justify-between bg-emerald-50 px-12 py-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-700 flex items-center justify-center text-white">
                    <i class="ti ti-wash-machine text-xl"></i>
                </div>
                <span class="text-lg font-semibold text-emerald-950">Mam Express</span>
            </div>

            <div>
                <span
                    class="inline-block text-xs font-medium tracking-wide text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full mb-6">
                    MAM EXPRESS WORKSPACE
                </span>
                <h1 class="text-4xl font-bold text-emerald-950 leading-tight mb-6">
                    Every load of laundry is taken care of.<br>
                    Every step of the process is recorded.
                </h1>
                <p class="text-emerald-900/70 max-w-sm">
                    Centralized access for operational teams and suppliers, according to their respective
                    responsibilities.
                </p>
            </div>

            <div class="flex items-start gap-2 text-sm text-emerald-900/70">
                <i class="ti ti-shield-check mt-0.5"></i>
                <span>Access rights are granted through an invitation or the administrator's approval.</span>
            </div>
        </div>

        <div class="flex flex-col justify-center px-6 sm:px-12 py-10">
            <div class="w-full max-w-md mx-auto">
                @yield('content')
            </div>
        </div>
    </div>
</body>

</html>
