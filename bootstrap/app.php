<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------------------
        | Middleware
        |--------------------------------------------------------------------------
        */

        // Jika aplikasi berada di belakang reverse proxy
        // (Nginx/Caddy/load balancer), konfigurasi trust proxy
        // dapat ditambahkan sesuai lingkungan produksi.
        //
        // Contoh:
        // $middleware->trustProxies(at: ['10.0.0.5']);

        /*
        |--------------------------------------------------------------------------
        | Global Web Middleware
        |--------------------------------------------------------------------------
        */

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => EnsureAccountActive::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        $middleware->redirectGuestsTo(
            fn () => route('login')
        );

        $middleware->redirectUsersTo(
            fn () => route('dashboard')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        |--------------------------------------------------------------------------
        | Validation / Sensitive Input
        |--------------------------------------------------------------------------
        |
        | Tanda tangan digital tidak disimpan ke flash session ketika
        | terjadi validation error karena ukurannya dapat besar dan
        | mengandung data sensitif.
        |
        */

        $exceptions->dontFlash([
            'tanda_tangan',
        ]);
    })
    ->create();