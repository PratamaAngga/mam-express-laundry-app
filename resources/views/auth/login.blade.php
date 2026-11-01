<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — Mam Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
</head>

<body class="min-h-screen bg-white text-[#12332d] flex flex-col">

    {{-- Header --}}
    <header class="px-6 lg:px-10 h-16 flex items-center justify-between border-b border-gray-100">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-teal-700 text-white flex items-center justify-center text-xl">
                <i class="ti ti-wash-machine"></i>
            </span>
            <span class="text-xl font-medium">Mam Express</span>
        </a>
        <p class="text-xs text-gray-500">
            Need help?
            {{-- TODO: replace '#' with the admin's WhatsApp link, e.g. https://wa.me/62812xxxxxxx --}}
            <a href="#" class="font-semibold text-teal-700 hover:underline">Contact admin</a>
        </p>
    </header>

    {{-- Main --}}
    <main class="flex-1 px-4 sm:px-6 lg:px-10 py-6">
        <div class="mx-auto max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">

            {{-- Left card (desktop only) --}}
            <section class="hidden lg:flex flex-col rounded-3xl bg-[#f6f1e7] p-8">
                <p class="text-[11px] font-semibold tracking-wider text-teal-700 uppercase">Candi Panggung Branch,
                    Malang</p>
                <h2 class="mt-3 text-3xl font-bold leading-tight">Our workplace, every day.</h2>
                <p class="mt-3 text-sm text-gray-600 max-w-md">
                    For the Mam Express cashier and supplier team. Orders, stock, and work records neatly in one place.
                </p>

                <div class="relative mt-6 flex-1 min-h-[300px] overflow-hidden rounded-2xl bg-[#c9dcc8]">
                    <img src="{{ asset('images/auth-hero.webp') }}"
                        alt="Laundry basket and detergent in front of a washing machine"
                        class="absolute inset-0 w-full h-full object-cover object-[center_75%]" onerror="this.remove()">
                </div>

                <p class="mt-5 flex items-center gap-2 text-xs text-gray-600">
                    <i class="ti ti-message-circle text-base text-teal-700"></i>
                    New here or can't sign in yet? Contact the admin.
                </p>
            </section>

            {{-- Right: form --}}
            <section class="flex items-center justify-center py-6">
                <div class="w-full max-w-md">
                    <p class="mb-3 text-[11px] font-semibold tracking-wider text-teal-700 uppercase">Sign in to Mam
                        Express</p>
                    <h1 class="text-3xl font-bold">Welcome back.</h1>
                    <p class="mt-2 mb-6 text-sm text-gray-500">Please sign in to continue today's work.</p>

                    @if (session('status'))
                        <div class="mb-4 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-lg px-4 py-3">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="login" class="block text-sm font-medium mb-1.5">Email or WhatsApp
                                number</label>
                            <input type="text" name="login" id="login" value="{{ old('login') }}"
                                autocomplete="username" autofocus placeholder="Enter your email or WhatsApp number"
                                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('login') border-red-500 @enderror">
                            @error('login')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium mb-1.5">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" autocomplete="current-password"
                                    placeholder="Enter your password"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('password') border-red-500 @enderror">
                                <button type="button" data-toggle-password="password" title="Show or hide password"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-lg text-gray-400 hover:text-gray-600">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="text-right">
                            {{-- Forgot password is not implemented yet --}}
                            <a href="#" class="text-xs font-semibold text-teal-700 hover:underline">Forgot
                                password?</a>
                        </div>

                        <button type="submit"
                            class="w-full bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg py-3 text-sm flex items-center justify-center gap-2">
                            <i class="ti ti-arrow-right"></i> Sign in
                        </button>

                        <div class="bg-teal-50 rounded-lg px-4 py-3 flex gap-3">
                            <i class="ti ti-circle-check text-teal-700 mt-0.5"></i>
                            <div class="text-xs">
                                <p class="font-semibold text-teal-900">Sign in with an approved account</p>
                                <p class="text-teal-800/80 mt-0.5">Employee and supplier accounts are activated by the
                                    admin. Page access follows the role set by the manager.</p>
                            </div>
                        </div>

                        <p class="text-center text-sm text-gray-500">
                            Don't have an account yet?
                            <a href="{{ route('register') }}" class="font-semibold text-teal-700 hover:underline">Sign
                                up here</a>
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="px-6 lg:px-10 py-5 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span>© {{ date('Y') }} Mam Express • Candi Panggung, Malang</span>
        <span class="flex items-center gap-5">
            <a href="#" class="hover:underline">Privacy Policy</a>
            <a href="#" class="hover:underline">Terms of Use</a>
        </span>
    </footer>

    <script>
        // Show / hide password
        document.querySelectorAll('[data-toggle-password]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.togglePassword);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = 'ti ' + (show ? 'ti-eye-off' : 'ti-eye');
            });
        });
    </script>
</body>

</html>
