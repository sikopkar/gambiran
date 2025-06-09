<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
    
        if (!$user) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }
    
        $routeName = $request->route() ? $request->route()->getName() : null;
    
        if (!$routeName) {
            return $next($request);
        }
    
        if (str_starts_with($routeName, 'anggota.') && $user->isKepalaKoperasi()) {
            return redirect('/home')->with('error', 'Kepala Koperasi tidak dapat mengakses halaman anggota.');
        }

        if (str_starts_with($routeName, 'pengguna.') && $user->isKepalaKoperasi()) {
            return redirect('/home')->with('error', 'Kepala Koperasi tidak dapat mengakses halaman pengguna.');
        }

        if (str_starts_with($routeName, 'simpanan.') && $user->isKepalaKoperasi()) {
            return redirect('/home')->with('error', 'Kepala Koperasi tidak dapat mengakses halaman simpanan.');
        }

        if (str_starts_with($routeName, 'pinjaman.') && $user->isKepalaKoperasi()) {
            return redirect('/home')->with('error', 'Kepala Koperasi tidak dapat mengakses halaman pinjaman.');
        }

        if (str_starts_with($routeName, 'angsuran.') && $user->isKepalaKoperasi()) {
            return redirect('/home')->with('error', 'Kepala Koperasi tidak dapat mengakses halaman pinjaman.');
        }

        return $next($request);
    }
}
