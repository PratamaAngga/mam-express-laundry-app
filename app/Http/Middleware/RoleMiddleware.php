<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /** Pemakaian: ->middleware('role:owner_admin') atau 'role:owner_admin,kasir' */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        // RoleMiddleware: pembatas akses per role dan pemutus sesi akun nonaktif
        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this page.');
        }

        // Akun yang dinonaktifkan owner langsung dikeluarkan, tidak menunggu sesinya habis
        if (! $user->status_aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Your account has been deactivated. Please contact the administrator.']);
        }

        return $next($request);
    }
}