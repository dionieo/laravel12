<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login.
     * Jika sudah login, redirect ke dashboard.
     */
    public function showForm()
    {
        if (Auth::check()) {
            return $this->redirectByLevel(Auth::user()->level);
        }

        return view('welcome');
    }

    /**
     * Proses login dengan username & password dari database.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        // Cek apakah user aktif
        $user = \App\Models\User::where('username', $request->username)->first();

        if (! $user) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Username tidak ditemukan.']);
        }

        if ($user->status !== 'aktif') {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Akun Anda tidak aktif. Hubungi administrator.']);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirectByLevel(Auth::user()->level);
        }

        return back()->withInput($request->only('username'))
            ->withErrors(['password' => 'Password salah.']);
    }

    /**
     * Logout dan hapus session.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }

    /**
     * Redirect berdasarkan level user.
     */
    private function redirectByLevel(string $level)
    {
        return match ($level) {
            'admin'  => redirect()->route('dashboard'),
            'siswa'  => redirect()->route('dashboard'),
            default  => redirect()->route('dashboard'),
        };
    }
}
