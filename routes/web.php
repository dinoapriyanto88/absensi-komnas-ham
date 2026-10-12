<?php

use App\Http\Controllers\AbsenController;
use App\Http\Controllers\AdminKegiatanController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SuperadminController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// PUBLIK: ABSENSI PESERTA
// Peserta hanya menggunakan tautan khusus dari Admin/penyelenggara kegiatan.
// ---------------------------------------------------------------------------

// Halaman root diarahkan ke login karena tidak ada halaman awal peserta.
Route::redirect('/', '/login');

// /absen tidak digunakan sebagai alur peserta.
// Alihkan ke login tanpa menghapus nama route absen.index.
Route::redirect('/absen', '/login')->name('absen.index');

// Halaman pilihan kategori untuk kegiatan tertentu.
Route::get('/absen/{kegiatan}', [AbsenController::class, 'pilihJenis'])
    ->name('absen.jenis');

// Formulir absensi sesuai kategori peserta.
Route::get('/absen/{kegiatan}/{jenis}', [AbsenController::class, 'form'])
    ->name('absen.form');

// Pencarian NIP melalui SIMPEG untuk peserta internal.
Route::post(
    '/absen/{kegiatan}/internal/cari-nip',
    [AbsenController::class, 'cariNip']
)
    ->middleware('throttle:cari-nip')
    ->name('absen.cari-nip');

// Pengiriman formulir absensi.
Route::post(
    '/absen/{kegiatan}/{jenis}',
    [AbsenController::class, 'submit']
)
    ->middleware('throttle:20,1')
    ->name('absen.submit');

// CAPTCHA.
Route::get('/captcha', CaptchaController::class)
    ->middleware('throttle:40,1')
    ->name('captcha');

// ---------------------------------------------------------------------------
// LOGIN: ADMIN DAN SUPERADMIN
// ---------------------------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
        ->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:10,1')
        ->name('password.store');
});

// ---------------------------------------------------------------------------
// AREA PENGGUNA YANG SUDAH LOGIN
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // Penggantian password, termasuk password sementara.
    Route::get('/akun/password', [AkunController::class, 'edit'])
        ->name('akun.password.edit');

    Route::put('/akun/password', [AkunController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('akun.password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');

        // -------------------------------------------------------------------
        // ADMIN
        // -------------------------------------------------------------------

        Route::middleware('role:admin')
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::get('/', [AdminKegiatanController::class, 'index'])
                    ->name('index');

                Route::post('/kegiatan', [AdminKegiatanController::class, 'store'])
                    ->name('store');

                // Admin hanya boleh mengelola kegiatan miliknya sendiri.
                Route::middleware('can:manage,kegiatan')
                    ->prefix('kegiatan/{kegiatan}')
                    ->group(function () {
                        Route::get('/', [AdminKegiatanController::class, 'show'])
                            ->name('show');

                        Route::get('/edit', [AdminKegiatanController::class, 'edit'])
                            ->name('edit');

                        Route::put('/', [AdminKegiatanController::class, 'update'])
                            ->name('update');

                        Route::patch('/status', [AdminKegiatanController::class, 'status'])
                            ->name('status');

                        Route::delete('/', [AdminKegiatanController::class, 'destroy'])
                            ->name('destroy');

                        Route::get('/export/pdf', [AdminKegiatanController::class, 'exportPdf'])
                            ->name('export.pdf');

                        Route::get('/export/csv', [AdminKegiatanController::class, 'exportCsv'])
                            ->name('export.csv');

                        Route::get(
                            '/tanda-tangan/{kehadiran}',
                            [AdminKegiatanController::class, 'tandaTangan']
                        )->name('tanda-tangan');
                    });
            });

        // -------------------------------------------------------------------
        // SUPERADMIN
        // -------------------------------------------------------------------

        Route::middleware('role:superadmin')
            ->prefix('superadmin')
            ->name('superadmin.')
            ->group(function () {
                Route::get('/', [SuperadminController::class, 'index'])
                    ->name('index');

                Route::post('/admin', [SuperadminController::class, 'store'])
                    ->name('store');

                Route::post(
                    '/admin/{admin}/reset-password',
                    [SuperadminController::class, 'resetPassword']
                )->name('reset');

                Route::patch(
                    '/admin/{admin}/aktif',
                    [SuperadminController::class, 'toggleAktif']
                )->name('aktif');

                Route::delete('/admin/{admin}', [SuperadminController::class, 'destroy'])
                    ->name('destroy');
            });
    });
});

