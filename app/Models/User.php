<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/** Hanya Admin dan Superadmin. Peserta tidak punya akun. */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN = 'admin';

    /**
     * `role` dan `must_change_password` SENGAJA tidak ada di sini, sehingga tidak bisa
     * diisi lewat mass assignment dari input pengguna (mencegah privilege escalation).
     */
    protected $fillable = ['username', 'nama', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class, 'created_by');
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /** Mengeluarkan akun ini dari semua perangkat (sesi tersimpan di database). */
    public function hapusSesiLain(?string $kecualiSesiId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->getKey());

        if ($kecualiSesiId !== null) {
            $query->where('id', '!=', $kecualiSesiId);
        }

        $query->delete();
    }
}