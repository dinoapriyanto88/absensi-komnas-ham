<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan';

    /** `slug`, `status`, dan `created_by` diisi eksplisit oleh server, bukan dari input. */
    protected $fillable = ['nama', 'deskripsi', 'mulai_at', 'selesai_at'];

    protected function casts(): array
    {
        return [
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Teks jadwal dari waktu mulai & selesai absen, mis. "Senin, 12 Oktober 2026, 08:00 – 12:00". */
    public function getJadwalAttribute(): string
    {
        $m = $this->mulai_at?->copy()->locale('id');
        $s = $this->selesai_at?->copy()->locale('id');

        if ($m && $s) {
            return $m->isSameDay($s)
                ? $m->translatedFormat('l, j F Y').', '.$m->format('H:i').' – '.$s->format('H:i')
                : $m->translatedFormat('j M Y, H:i').' — '.$s->translatedFormat('j M Y, H:i');
        }
        if ($m) {
            return 'Mulai '.$m->translatedFormat('l, j F Y, H:i');
        }
        if ($s) {
            return 'Sampai '.$s->translatedFormat('l, j F Y, H:i');
        }

        return 'Tanpa batas waktu';
    }

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class);
    }

    /**
     * @return array{buka: bool, alasan?: string, waktu?: Carbon}
     */
    public function jendela(?Carbon $sekarang = null): array
    {
        $sekarang ??= now();

        if ($this->status !== 'aktif') {
            return ['buka' => false, 'alasan' => 'ditutup'];
        }
        if ($this->mulai_at && $sekarang->lt($this->mulai_at)) {
            return ['buka' => false, 'alasan' => 'belum_mulai', 'waktu' => $this->mulai_at];
        }
        if ($this->selesai_at && $sekarang->gt($this->selesai_at)) {
            return ['buka' => false, 'alasan' => 'sudah_lewat', 'waktu' => $this->selesai_at];
        }

        return ['buka' => true];
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        $sekarang = now();

        return $query->where('status', 'aktif')
            ->where(fn (Builder $q) => $q->whereNull('mulai_at')->orWhere('mulai_at', '<=', $sekarang))
            ->where(fn (Builder $q) => $q->whereNull('selesai_at')->orWhere('selesai_at', '>=', $sekarang));
    }
}