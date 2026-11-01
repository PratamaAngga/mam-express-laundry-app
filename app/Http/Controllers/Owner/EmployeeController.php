<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    /**
     * Roles that count as "employees" (suppliers are external partners, so they are not listed here).
     */
    public const ROLES = [
        'kasir'              => 'Cashier',
        'petugas_processing' => 'Processing Staff',
        'owner_admin'        => 'Owner / Admin',
    ];

    /**
     * Employee: list, filters, and edit panel (FR-20).
     */
    public function index(Request $request)
    {
        $q            = trim((string) $request->query('q', ''));
        $roleFilter   = $request->query('role');
        $statusFilter = $request->query('status');

        $base = User::whereIn('role', array_keys(self::ROLES));

        // Stats ignore the filters
        $counts = [
            'total'    => (clone $base)->count(),
            'active'   => (clone $base)->where('status_aktif', true)->count(),
            'inactive' => (clone $base)->where('status_aktif', false)->count(),
        ];

        $employees = (clone $base)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('no_hp', 'like', "%{$q}%")))
            ->when(array_key_exists((string) $roleFilter, self::ROLES), fn ($query) => $query->where('role', $roleFilter))
            ->when($statusFilter === 'active', fn ($query) => $query->where('status_aktif', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('status_aktif', false))
            ->orderBy('id_user')
            ->get();

        // Employee shown in the edit panel (default: first row)
        $editing = (clone $base)->find($request->query('edit')) ?? $employees->first();

        return view('owner.employees.index', [
            'employees'    => $employees,
            'editing'      => $editing,
            'counts'       => $counts,
            'roles'        => self::ROLES,
            'q'            => $q,
            'roleFilter'   => $roleFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create()
    {
        return view('owner.employees.create', ['roles' => self::ROLES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:100', 'unique:users,email'],
            'no_hp'    => ['required', 'string', 'max:20', 'unique:users,no_hp'],
            'role'     => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Unique username, e.g. "kartiko-482"
        do {
            $username = Str::limit(Str::slug($data['nama']), 40, '') . '-' . random_int(100, 999);
        } while (User::where('username', $username)->exists());

        $user = User::create([
            'nama'         => $data['nama'],
            'username'     => $username,
            'email'        => $data['email'],
            'no_hp'        => $data['no_hp'],
            'role'         => $data['role'],
            'password'     => Hash::make($data['password']),
            'status_aktif' => true, // accounts created by the owner are active immediately
        ]);

        return redirect()
            ->route('owner.employees.index', ['edit' => $user->id_user])
            ->with('status', $user->nama . ' has been added as ' . self::ROLES[$user->role] . '.');
    }

    public function update(Request $request, User $user)
    {
        $this->ensureEmployee($user);

        $isSelf = $user->is($request->user());

        $data = $request->validate([
            'nama'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id_user, 'id_user')],
            'role'  => $isSelf ? ['nullable'] : ['required', Rule::in(array_keys(self::ROLES))],
        ]);

        // Owners cannot change their own role (prevents locking themselves out)
        if ($isSelf) {
            unset($data['role']);
        }

        $user->update($data);

        return redirect()
            ->route('owner.employees.index', ['edit' => $user->id_user])
            ->with('status', 'Account of ' . $user->nama . ' has been updated.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        $this->ensureEmployee($user);

        abort_if($user->is($request->user()), 403, 'You cannot deactivate your own account.');

        $user->update(['status_aktif' => ! $user->status_aktif]);

        return back()->with(
            'status',
            $user->nama . "'s account has been " . ($user->status_aktif ? 'activated.' : 'deactivated.')
        );
    }

    /** Only employee roles can be managed on this page (not suppliers). */
    private function ensureEmployee(User $user): void
    {
        abort_unless(array_key_exists($user->role, self::ROLES), 404);
    }
}