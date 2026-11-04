@extends('layouts.owner')

@section('title', 'Suppliers')
@section('crumb', 'Suppliers / ' . $supplier->nama_supplier)
@section('heading', 'Edit Supplier')
@section('subheading', $supplier->nama_supplier)

@php
    $input =
        'w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600';
    $blocked = $supplier->raw_materials_count + $supplier->restock_orders_count > 0;
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

    <div class="max-w-3xl space-y-6">
        <form method="POST" action="{{ route('owner.suppliers.update', $supplier) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            {{-- Supplier details --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Supplier details</h2>
                    @if ($supplier->is_active)
                        <span class="px-3 py-1 rounded-full text-xs bg-teal-50 text-teal-700">Active</span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </div>

                <div>
                    <label for="nama_supplier" class="block text-sm mb-1.5">Company name</label>
                    <input type="text" name="nama_supplier" id="nama_supplier" maxlength="100"
                        value="{{ old('nama_supplier', $supplier->nama_supplier) }}"
                        class="{{ $input }} @error('nama_supplier') border-red-500 @enderror">
                    @error('nama_supplier')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="no_whatsapp" class="block text-sm mb-1.5">WhatsApp number</label>
                    <input type="text" name="no_whatsapp" id="no_whatsapp" inputmode="tel"
                        value="{{ old('no_whatsapp', $supplier->no_whatsapp) }}"
                        class="{{ $input }} @error('no_whatsapp') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1.5">Also the number the supplier can sign in with.</p>
                    @error('no_whatsapp')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alamat" class="block text-sm mb-1.5">Address</label>
                    <textarea name="alamat" id="alamat" rows="3" maxlength="500"
                        class="{{ $input }} @error('alamat') border-red-500 @enderror">{{ old('alamat', $supplier->alamat) }}</textarea>
                    @error('alamat')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Account --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
                <h2 class="text-xl font-semibold">Supplier account</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="contact_name" class="block text-sm mb-1.5">Contact person</label>
                        <input type="text" name="contact_name" id="contact_name" maxlength="100"
                            value="{{ old('contact_name', $supplier->user->nama) }}"
                            class="{{ $input }} @error('contact_name') border-red-500 @enderror">
                        @error('contact_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm mb-1.5">Email (used to sign in)</label>
                        <input type="email" name="email" id="email" maxlength="100"
                            value="{{ old('email', $supplier->user->email) }}"
                            class="{{ $input }} @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm mb-1.5">New password (optional)</label>
                    <div class="flex gap-3">
                        <input type="password" name="password" id="password" autocomplete="new-password"
                            placeholder="Leave empty to keep the current password"
                            class="{{ $input }} @error('password') border-red-500 @enderror">
                        <button type="button" id="generate-password"
                            class="shrink-0 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 text-sm font-semibold">
                            Generate
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-3 text-sm">Save
                    Changes</button>
                <a href="{{ route('owner.suppliers.index') }}"
                    class="bg-white border border-gray-200 hover:bg-gray-50 font-semibold rounded-lg px-6 py-3 text-sm">Cancel</a>
            </div>
        </form>

        {{-- Usage --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl font-semibold mb-4">Usage</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-[#f4f8f7] rounded-xl px-5 py-4">
                    <dt class="text-gray-500">Materials supplied</dt>
                    <dd class="text-2xl font-bold mt-1">{{ $supplier->raw_materials_count }}</dd>
                </div>
                <div class="bg-[#f4f8f7] rounded-xl px-5 py-4">
                    <dt class="text-gray-500">Restock orders</dt>
                    <dd class="text-2xl font-bold mt-1">{{ $supplier->restock_orders_count }}</dd>
                </div>
            </dl>
        </div>

        {{-- Danger zone (separate forms, never nested inside the form above) --}}
        <div class="bg-white border border-red-200 rounded-2xl p-6">
            <h2 class="text-xl font-semibold text-red-700 mb-2">Deactivate or delete</h2>
            <p class="text-sm text-gray-500 mb-5">
                Deactivating blocks the supplier from signing in and keeps all history. Deleting is only possible for
                suppliers without materials or restock orders.
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('owner.suppliers.status', $supplier) }}"
                    @if ($supplier->is_active) onsubmit="return confirm('Deactivate this supplier? The supplier will lose access immediately.')" @endif>
                    @csrf
                    @method('PATCH')
                    @if ($supplier->is_active)
                        <button
                            class="bg-white border border-red-200 text-red-700 hover:bg-red-50 font-semibold rounded-lg px-5 py-3 text-sm">Deactivate
                            supplier</button>
                    @else
                        <button
                            class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-5 py-3 text-sm">Activate
                            supplier</button>
                    @endif
                </form>

                <form method="POST" action="{{ route('owner.suppliers.destroy', $supplier) }}"
                    onsubmit="return confirm('Delete this supplier permanently? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button @disabled($blocked)
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg px-5 py-3 text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                        Delete supplier
                    </button>
                </form>
            </div>

            @if ($blocked)
                <p class="text-xs text-gray-500 mt-3">Delete is disabled because this supplier already has materials or
                    restock orders.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
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
