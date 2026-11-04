@extends('layouts.owner')

@section('title', 'Suppliers')
@section('heading', 'Suppliers')
@section('subheading', 'Manage the suppliers that provide materials to the branch.')

@section('actions')
    <a href="{{ route('owner.suppliers.create') }}"
        class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-6 py-3.5">
        <i class="ti ti-plus"></i> Add Supplier
    </a>
@endsection

@section('content')
    @if (session('error'))
        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach ([['Total suppliers', $counts['total']], ['Active account', $counts['active']], ['Inactive account', $counts['inactive']]] as [$label, $value])
            <div class="bg-white border border-gray-200 rounded-2xl px-6 py-6 flex items-center justify-between">
                <span class="text-gray-500">{{ $label }}</span>
                <span class="text-3xl font-bold">{{ $value }}</span>
            </div>
        @endforeach
    </div>

    {{-- Search & filter --}}
    <form method="GET" class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="relative w-full max-w-md">
            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            <input type="text" name="q" value="{{ $q }}"
                placeholder="Search by company, contact, email, or WhatsApp"
                class="w-full bg-white border border-gray-200 rounded-lg pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
        </div>

        <div class="relative">
            <select name="status" onchange="this.form.submit()"
                class="appearance-none bg-white border border-gray-200 rounded-lg pl-5 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
            <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Supplier</th>
                    <th class="px-6 py-4 font-medium">Contact</th>
                    <th class="px-6 py-4 font-medium">WhatsApp</th>
                    <th class="px-6 py-4 font-medium">Materials</th>
                    <th class="px-6 py-4 font-medium">Restock orders</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($suppliers as $s)
                    <tr>
                        <td class="px-6 py-5">
                            <p class="font-semibold">{{ $s->nama_supplier }}</p>
                            <p class="text-xs text-gray-500 mt-0.5 max-w-xs truncate">{{ $s->alamat }}</p>
                        </td>
                        <td class="px-6 py-5">
                            <p>{{ $s->user->nama }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $s->user->email }}</p>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <a href="{{ $s->whatsapp_url }}" target="_blank" rel="noopener"
                                class="hover:underline">{{ $s->whatsapp_label }}</a>
                        </td>
                        <td class="px-6 py-5">{{ $s->raw_materials_count }}</td>
                        <td class="px-6 py-5">{{ $s->restock_orders_count }}</td>
                        <td class="px-6 py-5">
                            @if ($s->is_active)
                                <span class="px-3 py-1 rounded-full text-xs bg-teal-50 text-teal-700">Active</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center gap-5">
                                <a href="{{ route('owner.suppliers.edit', $s) }}"
                                    class="font-semibold text-teal-700 hover:underline">Edit</a>

                                <form method="POST" action="{{ route('owner.suppliers.status', $s) }}"
                                    @if ($s->is_active) onsubmit="return confirm('Deactivate {{ addslashes($s->nama_supplier) }}? The supplier will lose access immediately.')" @endif>
                                    @csrf
                                    @method('PATCH')
                                    @if ($s->is_active)
                                        <button class="text-red-600 hover:underline">Deactivate</button>
                                    @else
                                        <button class="text-teal-700 hover:underline">Activate</button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">No suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-sm text-gray-500">{{ $suppliers->count() }} {{ Str::plural('supplier', $suppliers->count()) }}
        shown. Deactivating keeps the supplier's history.</p>
@endsection
