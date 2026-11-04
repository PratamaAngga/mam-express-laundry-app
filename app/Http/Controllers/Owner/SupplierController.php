<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SupplierController extends Controller
{
    /**
     * Supplier list with search and status filter.
     */
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $like   = $this->like();

        $counts = [
            'total'  => Supplier::count(),
            'active' => Supplier::whereHas('user', fn ($u) => $u->where('status_aktif', true))->count(),
        ];
        $counts['inactive'] = $counts['total'] - $counts['active'];

        $suppliers = Supplier::with('user')
            ->withCount(['rawMaterials', 'restockOrders'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('nama_supplier', $like, "%{$q}%")
                ->orWhere('no_whatsapp', $like, "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('nama', $like, "%{$q}%")
                    ->orWhere('email', $like, "%{$q}%"))))
            ->when($status === 'active', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('status_aktif', true)))
            ->when($status === 'inactive', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('status_aktif', false)))
            ->orderBy('nama_supplier')
            ->get();

        return view('owner.suppliers.index', compact('suppliers', 'counts', 'q', 'status'));
    }

    public function create()
    {
        return view('owner.suppliers.create', ['linkable' => $this->linkableUsers()]);
    }

    /**
     * Creates the supplier profile plus its login account,
     * or links the profile to an existing supplier account that has no profile yet.
     */
    public function store(Request $request)
    {
        $this->normalizeInput($request);

        $linking = $request->filled('existing_user_id');

        $rules = [
            'nama_supplier'    => ['required', 'string', 'max:100', Rule::unique('supplier', 'nama_supplier')],
            // The WhatsApp number is also the login number of the supplier account
            'no_whatsapp'      => ['required', 'regex:/^08\d{8,12}$/',
                                   Rule::unique('users', 'no_hp')->ignore((int) $request->input('existing_user_id'), 'id_user')],
            'alamat'           => ['required', 'string', 'max:500'],
            'existing_user_id' => ['nullable',
                                   Rule::exists('users', 'id_user')->where('role', 'supplier'),
                                   Rule::unique('supplier', 'id_user')],
        ];

        if (! $linking) {
            $rules += [
                'contact_name' => ['required', 'string', 'max:100'],
                'email'        => ['required', 'email', 'max:100', 'unique:users,email'],
                'password'     => ['required', Password::min(8)->letters()->numbers()],
            ];
        }

        $data = $request->validate($rules, $this->messages());

        $supplier = DB::transaction(function () use ($data, $linking) {
            if ($linking) {
                // Linking an existing account also approves it
                $user = User::findOrFail($data['existing_user_id']);
                $user->update(['no_hp' => $data['no_whatsapp'], 'status_aktif' => true]);
            } else {
                $user = User::create([
                    'nama'         => trim($data['contact_name']),
                    'username'     => $this->uniqueUsername($data['contact_name']),
                    'email'        => $data['email'],
                    'no_hp'        => $data['no_whatsapp'],
                    'role'         => 'supplier',
                    'password'     => Hash::make($data['password']),
                    'status_aktif' => true,   // accounts created by the owner are active immediately
                ]);
            }

            return Supplier::create([
                'id_user'       => $user->id_user,
                'nama_supplier' => trim($data['nama_supplier']),
                'no_whatsapp'   => $data['no_whatsapp'],
                'alamat'        => trim($data['alamat']),
            ]);
        });

        return redirect()
            ->route('owner.suppliers.index')
            ->with('status', $supplier->nama_supplier . ' has been added as a supplier.');
    }

    public function edit(Supplier $supplier)
    {
        $supplier->load('user')->loadCount(['rawMaterials', 'restockOrders']);

        return view('owner.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $user = $supplier->user;

        $this->normalizeInput($request);

        $data = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:100',
                                Rule::unique('supplier', 'nama_supplier')->ignore($supplier->id_supplier, 'id_supplier')],
            'no_whatsapp'   => ['required', 'regex:/^08\d{8,12}$/',
                                Rule::unique('users', 'no_hp')->ignore($user->id_user, 'id_user')],
            'alamat'        => ['required', 'string', 'max:500'],
            'contact_name'  => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:100',
                                Rule::unique('users', 'email')->ignore($user->id_user, 'id_user')],
            'password'      => ['nullable', Password::min(8)->letters()->numbers()],
        ], $this->messages());

        DB::transaction(function () use ($data, $supplier, $user) {
            $supplier->update([
                'nama_supplier' => trim($data['nama_supplier']),
                'no_whatsapp'   => $data['no_whatsapp'],
                'alamat'        => trim($data['alamat']),
            ]);

            $account = [
                'nama'  => trim($data['contact_name']),
                'email' => $data['email'],
                'no_hp' => $data['no_whatsapp'],
            ];

            if (! empty($data['password'])) {
                $account['password'] = Hash::make($data['password']);
            }

            $user->update($account);
        });

        return redirect()
            ->route('owner.suppliers.index')
            ->with('status', $supplier->nama_supplier . ' has been updated.');
    }

    /** Activates or deactivates the supplier's login account. History is kept either way. */
    public function toggleStatus(Supplier $supplier)
    {
        $user = $supplier->user;
        $user->update(['status_aktif' => ! $user->status_aktif]);

        return back()->with(
            'status',
            $supplier->nama_supplier . ' has been ' . ($user->status_aktif ? 'activated.' : 'deactivated.')
        );
    }

    /**
     * Deletes a supplier that has no materials and no restock orders.
     * Suppliers with history can only be deactivated.
     */
    public function destroy(Supplier $supplier)
    {
        $materials = $supplier->rawMaterials()->count();
        $orders    = $supplier->restockOrders()->count();

        if ($materials > 0 || $orders > 0) {
            return back()->with(
                'error',
                $supplier->nama_supplier . ' cannot be deleted because it has ' . $materials . ' material(s) and '
                . $orders . ' restock order(s). Deactivate it instead.'
            );
        }

        $name = $supplier->nama_supplier;

        try {
            DB::transaction(function () use ($supplier) {
                $user = $supplier->user;
                $supplier->delete();
                $user?->delete();
            });
        } catch (QueryException $e) {
            return back()->with('error', $name . ' is still referenced by other data and cannot be deleted. Deactivate it instead.');
        }

        return redirect()->route('owner.suppliers.index')->with('status', $name . ' has been deleted.');
    }

    /** Supplier accounts that do not have a supplier profile yet (e.g. registered through the public form). */
    private function linkableUsers()
    {
        return User::where('role', 'supplier')
            ->whereNotIn('id_user', Supplier::select('id_user'))
            ->orderBy('nama')
            ->get();
    }

    private function normalizeInput(Request $request): void
    {
        $request->merge([
            'no_whatsapp' => $this->normalizePhone((string) $request->input('no_whatsapp')),
            'email'       => Str::lower(trim((string) $request->input('email'))),
        ]);
    }

    /** "08 12 3456 7890", "+62 812-3456-7890", "0812…" -> "081234567890" */
    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function uniqueUsername(string $name): string
    {
        do {
            $username = Str::limit(Str::slug($name), 40, '') . '-' . random_int(100, 999);
        } while (User::where('username', $username)->exists());

        return $username;
    }

    /** PostgreSQL's LIKE is case-sensitive, so use ILIKE there. */
    private function like(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    private function messages(): array
    {
        return [
            'nama_supplier.required'     => 'Company name is required.',
            'nama_supplier.unique'       => 'A supplier with this name already exists.',
            'no_whatsapp.required'       => 'WhatsApp number is required.',
            'no_whatsapp.regex'          => 'Please enter a valid WhatsApp number, e.g. 081234567890.',
            'no_whatsapp.unique'         => 'This WhatsApp number is already used by another account.',
            'alamat.required'            => 'Address is required.',
            'existing_user_id.exists'    => 'The selected account is not a supplier account.',
            'existing_user_id.unique'    => 'This account is already linked to a supplier.',
            'contact_name.required'      => 'Contact person name is required.',
            'email.required'             => 'Email is required.',
            'email.email'                => 'The email format is not valid.',
            'email.unique'               => 'This email is already registered.',
            'password.required'          => 'Password is required.',
        ];
    }
}