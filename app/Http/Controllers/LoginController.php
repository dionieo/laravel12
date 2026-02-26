<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        // Cek apakah password di DB sudah Bcrypt atau masih format lama (MD5/plain)
        $storedPassword = $user->password;
        $inputPassword  = $request->password;
        $passwordMatch  = false;

        if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$')) {
            // Password sudah Bcrypt — gunakan Auth::attempt biasa
            $passwordMatch = Hash::check($inputPassword, $storedPassword);
        } elseif (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword)) {
            // Password format MD5
            $passwordMatch = (md5($inputPassword) === $storedPassword);
        } else {
            // Password plain text
            $passwordMatch = ($inputPassword === $storedPassword);
        }

        if (! $passwordMatch) {
            return back()->withInput($request->only('username'))
                ->withErrors(['password' => 'Password salah.']);
        }

        // Re-hash password ke Bcrypt jika masih format lama
        if (! str_starts_with($storedPassword, '$2y$') && ! str_starts_with($storedPassword, '$2a$') && ! str_starts_with($storedPassword, '$2b$')) {
            $user->password = Hash::make($inputPassword);
            $user->timestamps = false;
            $user->save();
        }

        // Login manual
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectByLevel(Auth::user()->level);
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
