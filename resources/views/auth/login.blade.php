@extends('layouts.guest')

@section('title', 'Login — Mam Express')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h2>
    <p class="text-sm text-gray-500 mb-6">Log in to your account to continue working on Mam Express.</p>

    @if (session('status'))
        <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf

        <div>
            <label for="login" class="block text-sm text-gray-700 mb-1">Email or WhatsApp number</label>
            <input type="text" name="login" id="login" value="{{ old('login') }}"
                placeholder="Enter your account information"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('login') border-red-500 @enderror">
            @error('login')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm text-gray-700 mb-1">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('password') border-red-500 @enderror">
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="text-right">
            <a href="#" class="text-sm text-emerald-700 hover:underline">Forgot your password?</a>
        </div>

        <button type="submit"
            class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium rounded-lg py-2.5 text-sm flex items-center justify-center gap-2">
            <i class="ti ti-arrow-right"></i> Login
        </button>

        <div class="bg-emerald-50 rounded-lg px-4 py-3 flex gap-3">
            <i class="ti ti-circle-check text-emerald-700 mt-0.5"></i>
            <div class="text-sm">
                <p class="font-medium text-emerald-900">Use an approved account</p>
                <p class="text-emerald-800/80">Open-plan workspaces are assigned based on roles determined by management,
                    not by choice upon joining the company.</p>
            </div>
        </div>

        <p class="text-center text-sm text-gray-500">
            Don't have an account yet? <a href="{{ route('register') }}"
                class="text-emerald-700 font-medium hover:underline">Sign up now</a>
        </p>
    </form>
@endsection
