@extends('layouts.owner')

@section('title', 'Employee')
@section('heading', 'Add Employee')
@section('subheading', 'Create an active account and choose the access it needs.')

@section('content')
    <form method="POST" action="{{ route('owner.employees.store') }}"
        class="max-w-3xl bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="nama" class="block text-sm mb-1.5">Full Name</label>
                <input type="text" name="nama" id="nama" value="{{ old('nama') }}"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('nama') border-red-500 @enderror">
                @error('nama')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm mb-1.5">Role</label>
                <div class="relative">
                    <select name="role" id="role"
                        class="w-full appearance-none border border-gray-200 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('role') border-red-500 @enderror">
                        <option value="">Select a role</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
                @error('role')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm mb-1.5">Email akun</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="name@gmail.com"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="no_hp" class="block text-sm mb-1.5">WhatsApp Number</label>
                <input type="text" name="no_hp" id="no_hp" value="{{ old('no_hp') }}" placeholder="0812 3456 7890"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('no_hp') border-red-500 @enderror">
                @error('no_hp')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm mb-1.5">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('password') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">At least 8 characters; use a combination of letters and numbers.</p>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm mb-1.5">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-3 text-sm">Add
                Employee</button>
            <a href="{{ route('owner.employees.index') }}"
                class="bg-white border border-gray-200 hover:bg-gray-50 font-semibold rounded-lg px-6 py-3 text-sm">Cancel</a>
        </div>
    </form>
@endsection
