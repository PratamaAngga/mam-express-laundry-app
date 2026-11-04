@extends('layouts.owner')

@section('title', 'Suppliers')
@section('crumb', 'Suppliers / Add')
@section('heading', 'Add Supplier')
@section('subheading', 'Create the supplier profile and the account the supplier uses to sign in.')

@php
    $input =
        'w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 disabled:bg-gray-50';
@endphp

@section('actions')
    <a href="{{ route('owner.suppliers.index') }}"
        class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
        <i class="ti ti-arrow-left"></i> Back to List
    </a>
@endsection

@section('content')
    @if (session('error'))
        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('owner.suppliers.store') }}" class="max-w-3xl space-y-6">
        @csrf

        {{-- Supplier details --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
            <h2 class="text-xl font-semibold">Supplier details</h2>

            <div>
                <label for="nama_supplier" class="block text-sm mb-1.5">Company name</label>
                <input type="text" name="nama_supplier" id="nama_supplier" value="{{ old('nama_supplier') }}"
                    maxlength="100" placeholder="e.g. PT Aroma Bersih"
                    class="{{ $input }} @error('nama_supplier') border-red-500 @enderror">
                @error('nama_supplier')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="no_whatsapp" class="block text-sm mb-1.5">WhatsApp number</label>
                <input type="text" name="no_whatsapp" id="no_whatsapp" value="{{ old('no_whatsapp') }}" inputmode="tel"
                    placeholder="e.g. 0811 8800 5500"
                    class="{{ $input }} @error('no_whatsapp') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1.5">Restock invoices are sent to this number. It is also the number the
                    supplier can sign in with.</p>
                @error('no_whatsapp')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="alamat" class="block text-sm mb-1.5">Address</label>
                <textarea name="alamat" id="alamat" rows="3" maxlength="500" placeholder="Street, district, city"
                    class="{{ $input }} @error('alamat') border-red-500 @enderror">{{ old('alamat') }}</textarea>
                @error('alamat')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Account --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
            <h2 class="text-xl font-semibold">Supplier account</h2>

            @if ($linkable->isNotEmpty())
                <div>
                    <label for="existing_user_id" class="block text-sm mb-1.5">Use an existing supplier account
                        (optional)</label>
                    <div class="relative">
                        <select name="existing_user_id" id="existing_user_id"
                            class="{{ $input }} appearance-none pr-10 @error('existing_user_id') border-red-500 @enderror">
                            <option value="">Create a new account</option>
                            @foreach ($linkable as $u)
                                <option value="{{ $u->id_user }}" @selected((string) old('existing_user_id') === (string) $u->id_user)>
                                    {{ $u->nama }} —
                                    {{ $u->email }}{{ $u->status_aktif ? '' : ' (pending approval)' }}
                                </option>
                            @endforeach
                        </select>
                        <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5">These accounts signed up as suppliers but have no supplier
                        profile yet. Linking one also approves it.</p>
                    @error('existing_user_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div id="link-hint" class="hidden bg-teal-50 rounded-lg px-4 py-3 text-sm text-teal-800">
                The selected account keeps its own name, email, and password. Only the supplier profile above is created.
            </div>

            <div id="new-account" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="contact_name" class="block text-sm mb-1.5">Contact person</label>
                        <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name') }}"
                            maxlength="100" placeholder="e.g. Nadia Ananda"
                            class="{{ $input }} @error('contact_name') border-red-500 @enderror">
                        @error('contact_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm mb-1.5">Email (used to sign in)</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" maxlength="100"
                            placeholder="e.g. supplier@gmail.com"
                            class="{{ $input }} @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm mb-1.5">Password</label>
                    <div class="flex gap-3">
                        <input type="password" name="password" id="password" autocomplete="new-password"
                            class="{{ $input }} @error('password') border-red-500 @enderror">
                        <button type="button" id="generate-password"
                            class="shrink-0 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 text-sm font-semibold">
                            Generate
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5">At least 8 characters, letters and numbers. Share it with the
                        supplier after saving, because it cannot be viewed again.</p>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-3 text-sm">Add
                Supplier</button>
            <a href="{{ route('owner.suppliers.index') }}"
                class="bg-white border border-gray-200 hover:bg-gray-50 font-semibold rounded-lg px-6 py-3 text-sm">Cancel</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const existing = document.getElementById('existing_user_id');
        const newAccount = document.getElementById('new-account');
        const linkHint = document.getElementById('link-hint');

        // Hide the new-account fields when an existing account is chosen
        function syncAccountMode() {
            const linking = !!existing && existing.value !== '';
            newAccount.classList.toggle('hidden', linking);
            linkHint.classList.toggle('hidden', !linking);
            newAccount.querySelectorAll('input').forEach(i => i.disabled = linking);
        }
        if (existing) existing.addEventListener('change', syncAccountMode);
        syncAccountMode();

        // Random password with letters and numbers
        document.getElementById('generate-password').addEventListener('click', () => {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            const values = new Uint32Array(10);
            crypto.getRandomValues(values);
            const password = Array.from(values, n => chars[n % chars.length]).join('') + 'a7';

            const input = document.getElementById('password');
            input.type = 'text';
            input.value = password;
        });
    </script>
@endpush
