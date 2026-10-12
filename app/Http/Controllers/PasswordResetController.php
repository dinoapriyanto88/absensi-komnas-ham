<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['username' => ['required', 'string', 'max:60']]);

        $user = User::where('username', Str::lower(trim($request->input('username'))))->first();

        if ($user && $user->email) {
            try {
                Password::sendResetLink(['email' => $user->email]);
            } catch (\Throwable $e) {
                // Kegagalan SMTP dicatat di log, tidak dibocorkan ke pengguna.
                report($e);
            }
        }

        // Tautan reset TIDAK PERNAH ditampilkan di layar, dan balasan selalu sama,
        // sehingga penyerang tidak bisa menebak username atau mengambil alih akun.
        return back()->with('status', 'Jika akun tersebut ada dan memiliki email terdaftar, tautan reset sudah dikirim. Periksa kotak masuk atau folder spam.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:200', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                $user->hapusSesiLain();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diganti, silakan login.');
        }

        return back()->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.']);
    }
}
