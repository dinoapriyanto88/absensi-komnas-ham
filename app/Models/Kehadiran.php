<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kehadiran extends Model
{
    protected $table = 'kehadiran';

    protected $fillable = [
        'kegiatan_id', 'jenis_peserta', 'nama', 'tipe_peserta',
        'jabatan', 'instansi', 'unit_kerja', 'nip_nik', 'no_telp',
        'kunci_unik', 'tanda_tangan_path',
    ];

    protected $hidden = ['kunci_unik'];

    protected function casts(): array
    {
        return [
            'nip_nik' => 'encrypted',
            'no_telp' => 'encrypted',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function labelNipNik(): string
    {
        return $this->jenis_peserta === 'internal' ? 'NIP' : 'NIK';
    }
}
