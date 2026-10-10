@extends('layouts.guest')

@section('title', 'Daftar — Mam Express')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Sign up for an account</h2>
    <p class="text-sm text-gray-500 mb-6">Please provide your personal information to request access to the Mam Express
        workspace.</p>

    <form method="POST" action="{{ route('register.attempt') }}" class="space-y-4">
        @csrf

        <div>
            <label for="nama" class="block text-sm text-gray-700 mb-1">Full Name</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama') }}"
                placeholder="Name as listed on identification"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('nama') border-red-500 @enderror">
            @error('nama')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="no_hp" class="block text-sm text-gray-700 mb-1">WhatsApp Number</label>
            <input type="text" name="no_hp" id="no_hp" value="{{ old('no_hp') }}"
                placeholder="Example: 0812 3456 7890"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('no_hp') border-red-500 @enderror">
            @error('no_hp')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm text-gray-700 mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="name@gmail.com"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="role" class="block text-sm text-gray-700 mb-1">Sign up as</label>
            <select name="role" id="role"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('role') border-red-500 @enderror">
                <option value="">Select a role</option>
                <option value="kasir" {{ old('role') == 'kasir' ? 'selected' : '' }}>Cashier</option>
                <option value="petugas_processing" {{ old('role') == 'petugas_processing' ? 'selected' : '' }}>Processing
                    Employee</option>
                <option value="supplier" {{ old('role') == 'supplier' ? 'selected' : '' }}>Supplier</option>
            </select>
            @error('role')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm text-gray-700 mb-1">Password</label>
            <input type="password" name="password" id="password" placeholder="Create a password"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 @error('password') border-red-500 @enderror">
            <p class="text-xs text-gray-400 mt-1">At least 8 characters; use a combination of letters and numbers.</p>
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                placeholder="Re-enter your password"
                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div class="bg-emerald-50 rounded-lg px-4 py-3 flex gap-3">
            <i class="ti ti-circle-check text-emerald-700 mt-0.5"></i>
            <div class="text-sm">
                <p class="font-medium text-emerald-900">Operational accounts require approval</p>
                <p class="text-emerald-800/80">The roles of staff members or suppliers are assigned by the administrator
                    through invitations or approvals Registration does not grant administrative privileges.</p>
            </div>
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-600">
            <input type="checkbox" name="terms"
                class="mt-1 rounded border-gray-300 text-emerald-700 focus:ring-emerald-600 @error('terms') border-red-500 @enderror">
            <span>I agree to Mam Express's Terms of Use and Privacy Policy.</span>
        </label>
        @error('terms')
            <p class="text-red-500 text-xs -mt-2">{{ $message }}</p>
        @enderror

        <button type="submit"
            class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium rounded-lg py-2.5 text-sm flex items-center justify-center gap-2">
            <i class="ti ti-arrow-right"></i> Submit Your Application
        </button>

        <p class="text-center text-sm text-gray-500">
            Already have an account? <a href="{{ route('login') }}"
                class="text-emerald-700 font-medium hover:underline">Click here</a>
        </p>
    </form>
@endsection
