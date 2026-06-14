<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccess
{
    /**
     * Middleware RBAC — memvalidasi peran pengguna terotentikasi.
     *
     * Mendukung sliding window session regeneration setiap 15 menit
     * untuk memitigasi risiko session fixation di jaringan bencana
     * yang sering berganti gateway nirkabel.
     *
     * @param  string  ...$roles  Daftar peran yang diizinkan
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Pastikan user terotentikasi
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Validasi peran
        if (!empty($roles) && !in_array($request->user()->role, $roles)) {
            abort(403, 'Access denied. Your role is not authorized to access this page.');
        }

        // Sliding window session regeneration (15 menit)
        $lastRegenerated = $request->session()->get('_last_regenerated', 0);
        $now = time();

        if ($now - $lastRegenerated > 900) { // 900 detik = 15 menit
            $request->session()->regenerate();
            $request->session()->put('_last_regenerated', $now);
        }

        return $next($request);
    }
}
