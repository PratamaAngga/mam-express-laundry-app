<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Login with an email OR a WhatsApp number.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required'    => 'Email or WhatsApp number is required.',
            'password.required' => 'Password is required.',
        ]);

        $login = trim($credentials['login']);

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::whereRaw('LOWER(email) = ?', [Str::lower($login)])->first();
        } else {
            // Try the number as typed and in its normalized form (e.g. "0812 3456 7890" -> "081234567890")
            $user = User::whereIn('no_hp', array_unique([$login, $this->normalizePhone($login)]))->first();
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'The email/WhatsApp number or password is incorrect.'])
                ->onlyInput('login');
        }

        if (! $user->status_aktif) {
            return back()
                ->withErrors(['login' => 'Your account has not been approved by the admin yet. Please contact the admin for activation.'])
                ->onlyInput('login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        // The "dashboard" route sends each role to its own home page
        return redirect()->intended('/dashboard');
    }

    /**
     * Show the register page.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Register. The account is saved as inactive (status_aktif = false)
     * until the Owner/Admin approves it on the Employee page.
     */
    public function register(Request $request)
    {
        // Normalize before validating so uniqueness compares the same format
        $request->merge([
            'no_hp' => $this->normalizePhone((string) $request->input('no_hp')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'no_hp'    => ['required', 'regex:/^08\d{8,12}$/', 'unique:users,no_hp'],
            'email'    => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            // Only validated when the "Sign up as" field is in the form
            'role'     => ['sometimes', 'required', Rule::in(['kasir', 'petugas_processing', 'supplier'])],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms'    => ['accepted'],
        ], [
            'nama.required'      => 'Full name is required.',
            'no_hp.required'     => 'WhatsApp number is required.',
            'no_hp.regex'        => 'Please enter a valid WhatsApp number, e.g. 081234567890.',
            'no_hp.unique'       => 'This WhatsApp number is already registered.',
            'email.required'     => 'Email is required.',
            'email.email'        => 'The email format is not valid.',
            'email.unique'       => 'This email is already registered.',
            'role.required'      => 'Please select a role.',
            'role.in'            => 'Please select a valid role.',
            'password.required'  => 'Password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'terms.accepted'     => 'You must agree to the Terms of Use and Privacy Policy.',
        ]);

        User::create([
            'nama'         => trim($validated['nama']),
            'username'     => $this->uniqueUsername($validated['nama']),
            'email'        => $validated['email'],
            'no_hp'        => $validated['no_hp'],
            'role'         => $validated['role'] ?? 'kasir',   // default when the role field is not in the form
            'password'     => Hash::make($validated['password']),
            'status_aktif' => false,
        ]);

        return redirect()->route('login')->with(
            'status',
            'Registration successful! Your account is waiting for admin approval before it can be used.'
        );
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
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
}