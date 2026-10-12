<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    private const JEDA_DETIK = 300;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $username = Str::lower(trim($data['username']));
        $kunci = 'login:'.$username.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $kredensial = [
            'username' => $username,
            'password' => $data['password'],
        ];

        if (! Auth::validate($kredensial)) {
            RateLimiter::hit($kunci, self::JEDA_DETIK);

            // Pesan sengaja sama untuk "username tidak ada" dan "password salah".
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        $user = User::where('username', $username)->firstOrFail();

        // Pesan khusus hanya muncul setelah password terbukti benar,
        // jadi tidak membuka celah menebak akun.
        if ($user->is_active === false) {
            throw ValidationException::withMessages([
                'username' => 'Akun ini dinonaktifkan. Hubungi Superadmin.',
            ]);
        }

        Auth::login($user);

        RateLimiter::clear($kunci);

        $request->session()->regenerate(); // cegah session fixation

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}