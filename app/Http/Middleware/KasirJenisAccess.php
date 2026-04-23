<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KasirJenisAccess
{
    protected array $mapJenisKasir = [
        1 => [1],
        2 => [2, 3, 4, 5, 6],
        3 => [4],
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $jenisKasir = (int) $request->query('jenis_kasir');

        if (!$jenisKasir) {
            return $next($request);
        }

        $allowed = $this->mapJenisKasir[$user->role_id] ?? [];

        if (!in_array($jenisKasir, $allowed)) {
            abort(403, 'Anda tidak memiliki akses ke kasir jenis ini.');
        }

        return $next($request);
    }
}
