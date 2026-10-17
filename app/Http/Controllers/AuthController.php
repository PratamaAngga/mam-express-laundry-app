<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Proses login — menerima email ATAU nomor WhatsApp sebagai identitas.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'no_hp';

        $user = User::where($field, $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Email/nomor WhatsApp atau password salah.'])
                ->onlyInput('login');
        }

        if (! $user->status_aktif) {
            return back()
                ->withErrors(['login' => 'Akun Anda belum disetujui administrator. Hubungi admin untuk aktivasi.'])
                ->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    /**
     * Tampilkan halaman register.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Proses register — akun baru tersimpan non-aktif (status_aktif = false)
     * sampai disetujui & diberi role oleh Owner/Admin.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:255'],
            'no_hp'    => ['required', 'string', 'max:20', 'unique:users,no_hp'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', 'in:kasir,petugas_processing,supplier'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms'    => ['accepted'],
        ]);

        User::create([
            'nama'         => $validated['nama'],
            'username'     => Str::slug($validated['nama']) . '-' . random_int(100, 999),
            'email'        => $validated['email'],
            'no_hp'        => $validated['no_hp'],
            'role'         => $validated['role'],
            'password'     => Hash::make($validated['password']),
            'status_aktif' => false,
        ]);

        return redirect()->route('login')->with(
            'status',
            'Pendaftaran berhasil! Akun Anda menunggu persetujuan administrator sebelum dapat digunakan.'
        );
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
