<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Halaman login.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.login');
    }

    /**
     * Proses otentikasi.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $request->session()->put('_last_regenerated', time());

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'Kredensial tidak valid.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect berdasarkan peran pengguna.
     */
    private function redirectByRole($user)
    {
        return match ($user->role) {
            'Pusat' => redirect()->route('admin-pusat.dashboard'),
            'Daerah' => redirect()->route('admin-daerah.dashboard'),
            'SAR' => redirect()->route('sar.dashboard'),
            'Koor' => redirect()->route('koordinator.dashboard'),
            default => redirect('/'),
        };
    }
}
