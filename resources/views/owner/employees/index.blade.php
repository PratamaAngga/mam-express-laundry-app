@extends('layouts.owner')

@section('title', 'Employee')
@section('heading', 'Employee')
@section('subheading', 'Manage accounts and grant team access according to their responsibilities.')

@section('actions')
    <a href="{{ route('owner.employees.create') }}"
        class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-semibold rounded-lg px-6 py-3.5">
        <i class="ti ti-plus"></i> Add Employee
    </a>
@endsection

@section('content')
    @php
        // Keep the active filters when clicking "Change"
        $filters = array_filter(['q' => $q, 'role' => $roleFilter, 'status' => $statusFilter]);

        $cards = [
            ['label' => 'Total number of employees', 'value' => $counts['total']],
            ['label' => 'Active account', 'value' => $counts['active']],
            ['label' => 'Inactive account', 'value' => $counts['inactive']],
        ];

        $access = [
            'kasir' => [
                'Cashier',
                'New Orders, Order List, Customers, and Print Receipts. Without access to reports, inventory, or prices.',
            ],
            'petugas_processing' => [
                'Processing Staff',
                'Order queue and status updates for washing, processing, and packing.',
            ],
            'owner_admin' => [
                'Owner / Admin',
                'Dashboard, inventory, suppliers, reports, employee accounts, and service pricing settings.',
            ],
        ];
    @endphp

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach ($cards as $c)
            <div class="bg-white border border-gray-200 rounded-2xl px-6 py-6 flex items-center justify-between">
                <span class="text-gray-500">{{ $c['label'] }}</span>
                <span class="text-3xl font-bold">{{ $c['value'] }}</span>
            </div>
        @endforeach
    </div>

    {{-- Search & filters --}}
    <form method="GET" class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="relative w-full max-w-md">
            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Search for an employee's name"
                class="w-full bg-white border border-gray-200 rounded-lg pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <select name="role" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-5 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($roleFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>

            <div class="relative">
                <select name="status" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-5 pr-10 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-600">
                    <option value="">All statuses</option>
                    <option value="active" @selected($statusFilter === 'active')>Aktif</option>
                    <option value="inactive" @selected($statusFilter === 'inactive')>Nonaktif</option>
                </select>
                <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-[#eef3f2] text-left text-gray-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Employee</th>
                    <th class="px-6 py-4 font-medium">Role</th>
                    <th class="px-6 py-4 font-medium">Account Status</th>
                    <th class="px-6 py-4 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employees as $u)
                    <tr class="{{ $editing && $editing->is($u) ? 'bg-teal-50/40' : '' }}">
                        <td class="px-6 py-5 font-semibold">{{ $u->nama }}</td>
                        <td class="px-6 py-5">{{ $roles[$u->role] ?? $u->role }}</td>
                        <td class="px-6 py-5">
                            @if ($u->status_aktif)
                                <span class="px-3 py-1 rounded-full text-xs bg-teal-50 text-teal-700">Aktif</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-5">
                                <a href="{{ route('owner.employees.index', $filters + ['edit' => $u->id_user]) }}"
                                    class="font-semibold text-teal-700 hover:underline">Change</a>

                                @if ($u->is(auth()->user()))
                                    <span class="text-red-600">Your Account</span>
                                @else
                                    <form method="POST" action="{{ route('owner.employees.status', $u) }}"
                                        @if ($u->status_aktif) onsubmit="return confirm('Deactivate this account? The employee will lose access.')" @endif>
                                        @csrf
                                        @method('PATCH')
                                        @if ($u->status_aktif)
                                            <button class="text-red-600 hover:underline">Nonaktifkan</button>
                                        @else
                                            <button class="text-teal-700 hover:underline">Aktifkan</button>
                                        @endif
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-400">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Edit account --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl">Edit an employee's account</h2>

            @if ($editing)
                @php $isSelf = $editing->is(auth()->user()); @endphp
                <p class="text-sm text-gray-500 mb-6">{{ $editing->nama }} •
                    {{ $roles[$editing->role] ?? $editing->role }}</p>

                <form method="POST" action="{{ route('owner.employees.update', $editing) }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nama" class="block text-sm mb-1.5">Name</label>
                            <input type="text" name="nama" id="nama" value="{{ old('nama', $editing->nama) }}"
                                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('nama') border-red-500 @enderror">
                            @error('nama')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="block text-sm mb-1.5">Role</label>
                            <div class="relative">
                                <select name="role" id="role" @disabled($isSelf)
                                    class="w-full appearance-none border border-gray-200 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 disabled:bg-gray-50 disabled:text-gray-500 @error('role') border-red-500 @enderror">
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected(old('role', $editing->role) === $value)>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                                <i
                                    class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            </div>
                            @if ($isSelf)
                                <p class="text-xs text-gray-400 mt-1">You cannot change your own role.</p>
                            @endif
                            @error('role')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm mb-1.5">Email akun</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $editing->email) }}"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                            class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-3 text-sm">Save
                            Changes</button>
                        <a href="{{ route('owner.employees.index', $filters) }}"
                            class="bg-white border border-gray-200 hover:bg-gray-50 font-semibold rounded-lg px-6 py-3 text-sm">Cancel</a>
                    </div>
                </form>
            @else
                <p class="text-gray-400 py-8">Select an employee to edit the account.</p>
            @endif
        </div>

        {{-- Role-based access rights --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl mb-5">Role-based access rights</h2>

            <div class="divide-y divide-gray-200">
                @foreach ($access as $key => [$title, $text])
                    <div class="py-4 first:pt-0">
                        <p class="font-semibold {{ $editing && $editing->role === $key ? 'text-teal-700' : '' }}">
                            {{ $title }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 bg-teal-50 rounded-xl px-5 py-4 flex gap-3">
                <i class="ti ti-circle-check text-xl text-teal-700 mt-0.5"></i>
                <div>
                    <p class="font-semibold text-teal-800">The history remains saved</p>
                    <p class="text-sm text-teal-800">Deactivating an account revokes access; it does not delete the
                        employee's transactions.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
