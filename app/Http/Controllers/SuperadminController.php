<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AkunAdminDibuatNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SuperadminController extends Controller
{
    public function index(): View
    {
        $admins = User::where('role', User::ROLE_ADMIN)
            ->withCount('kegiatan')
            ->orderBy('nama')
            ->get();

        return view('superadmin.dashboard', [
            'admins' => $admins,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._-]+$/',
                'unique:users,username',
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:150',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'max:200',
                Password::defaults(),
            ],
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, garis bawah, atau tanda hubung.',
            'username.unique' => 'Username tersebut sudah digunakan.',
            'email.required' => 'Email wajib diisi untuk mengirim notifikasi akun.',
            'email.unique' => 'Email tersebut sudah digunakan.',
        ]);

        $admin = new User($data);

        $admin->role = User::ROLE_ADMIN;
        $admin->must_change_password = true;
        $admin->is_active = true;
        $admin->save();

        $pesan = 'Akun admin berhasil dibuat. Admin akan diminta mengganti password saat login pertama.';

        try {
            $admin->notify(new AkunAdminDibuatNotification(
                dibuatOleh: $request->user()->nama
            ));

            $pesan .= ' Email pemberitahuan sudah dikirim ke '.$admin->email.'.';
        } catch (\Throwable $e) {
            // Akun tetap dibuat; detail error dicatat di storage/logs/laravel.log.
            report($e);

            $pesan .= ' Namun email pemberitahuan GAGAL dikirim ('.class_basename($e).'). '
                .'Cek pengaturan SMTP dan sampaikan informasi akun secara langsung.';
        }

        return redirect()
            ->route('superadmin.index')
            ->with('status', $pesan);
    }

    /**
     * Mengaktifkan/menonaktifkan akun Admin TANPA menghapus
     * akun maupun kegiatan dan datanya.
     */
    public function toggleAktif(User $admin): RedirectResponse
    {
        abort_unless($admin->isAdmin(), 404);

        $admin->is_active = ! ($admin->is_active ?? true);
        $admin->save();

        if (! $admin->is_active) {
            $admin->setRememberToken(Str::random(60));
            $admin->save();

            // Keluarkan Admin dari semua perangkat saat ini juga.
            $admin->hapusSesiLain();
        }

        return redirect()
            ->route('superadmin.index')
            ->with(
                'status',
                $admin->is_active
                    ? "Akun {$admin->username} diaktifkan kembali."
                    : "Akun {$admin->username} dinonaktifkan. Akun dan datanya tetap tersimpan."
            );
    }

    public function resetPassword(User $admin): RedirectResponse
    {
        abort_unless($admin->isAdmin(), 404);

        $sementara = Str::password(14, symbols: false);

        $admin->forceFill([
            'password' => $sementara,
            'must_change_password' => true,
        ])->save();

        $admin->hapusSesiLain();

        // Dikirim lewat flash session (tampil sekali), bukan lewat URL,
        // supaya tidak tertinggal di riwayat browser atau log server.
        return redirect()
            ->route('superadmin.index')
            ->with([
                'password_sementara' => $sementara,
                'password_sementara_untuk' => $admin->username,
            ]);
    }

    public function destroy(User $admin): RedirectResponse
    {
        abort_unless($admin->isAdmin(), 404);

        $jumlah = $admin->kegiatan()->count();

        if ($jumlah > 0) {
            return redirect()
                ->route('superadmin.index')
                ->withErrors([
                    'admin' => "Akun {$admin->username} masih memiliki {$jumlah} kegiatan. Nonaktifkan akun jika ingin mempertahankan data kegiatan.",
                ]);
        }

        $admin->delete();

        return redirect()
            ->route('superadmin.index')
            ->with('status', "Akun {$admin->username} berhasil dihapus.");
    }
}