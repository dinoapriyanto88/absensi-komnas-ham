<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Aturan password tunggal untuk seluruh aplikasi.
        Password::defaults(
            fn () => Password::min(10)->mixedCase()->numbers()
        );

        // Pembatas percobaan pencarian data NIP melalui SIMPEG.
        RateLimiter::for('cari-nip', function (Request $request) {
            return Limit::perMinute(10)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Jika APP_URL menggunakan HTTPS, paksa tautan memakai HTTPS.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
