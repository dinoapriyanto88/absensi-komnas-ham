<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian data pegawai berdasarkan NIP di database kepegawaian (hanya-baca).
 *
 * Hanya tiga data yang pernah keluar dari kelas ini: nama, jabatan, dan unit kerja.
 * Telepon, email, alamat, dan data pribadi lain di tb_pegawai sengaja TIDAK dibaca,
 * karena pencarian ini bisa dipanggil dari halaman publik.
 */
final class Simpeg
{
    public const TIDAK_ADA = 'tidak_ada';

    public const GANDA = 'ganda';

    public const ERROR = 'error';

    public const OK = 'ok';

    public static function aktif(): bool
    {
        return (bool) config('simpeg.enabled');
    }

    /**
     * @return array{status: string, data?: array{nama: string, jabatan: string, unit_kerja: string}}
     */
    public static function cari(string $nipInput): array
    {
        $nip = preg_replace('/\D+/', '', $nipInput) ?? '';

        if ($nip === '') {
            return ['status' => self::TIDAK_ADA];
        }

        try {
            $db = DB::connection((string) config('simpeg.connection'));

            // limit(2) cukup untuk mengetahui apakah NIP dipakai lebih dari satu pegawai.
            $baris = $db->table('tb_pegawai')
                ->whereRaw('TRIM(nip) = ?', [$nip])
                ->limit(2)
                ->get(['nama', 'unit_kerja', 'id_masterjab']);

            if ($baris->isEmpty()) {
                return ['status' => self::TIDAK_ADA];
            }

            // NIP seharusnya unik. Jika ganda, JANGAN menebak siapa yang dimaksud:
            // menampilkan nama orang lain lebih buruk daripada meminta data diperbaiki.
            if ($baris->count() > 1) {
                return ['status' => self::GANDA];
            }

            $p = $baris->first();

            return ['status' => self::OK, 'data' => [
                'nama' => self::rapikan($p->nama),
                'jabatan' => self::rujuk($db, $p->id_masterjab, 'tb_masterjab', 'id_masterjab', 'nama_masterjab') ?: '-',
                'unit_kerja' => self::rujuk($db, $p->unit_kerja, 'tb_unit', 'id_unit', 'nama_unit') ?: '-',
            ]];
        } catch (\Throwable $e) {
            report($e);

            return ['status' => self::ERROR];
        }
    }

    /** Pesan untuk peserta. Sengaja tidak membedakan sebab teknis. */
    public static function pesan(string $status): string
    {
        return match ($status) {
            self::TIDAK_ADA => 'NIP tidak ditemukan di data kepegawaian. Periksa kembali nomor Anda atau hubungi penyelenggara.',
            self::GANDA => 'NIP ini terdaftar lebih dari satu pegawai. Hubungi bagian kepegawaian untuk memperbaiki data.',
            default => 'Data kepegawaian sedang tidak dapat diakses. Coba lagi beberapa saat atau hubungi penyelenggara.',
        };
    }

    public static function kodeHttp(string $status): int
    {
        return match ($status) {
            self::TIDAK_ADA => 404,
            self::GANDA => 409,
            default => 503,
        };
    }

    /**
     * Kolom id_masterjab dan unit_kerja di data sumber campur: kadang berisi ID angka
     * (dirujuk ke tabel master), kadang langsung berisi teks nama. Keduanya didukung.
     */
    private static function rujuk(
        ConnectionInterface $db,
        mixed $nilai,
        string $tabel,
        string $kunci,
        string $kolomNama
    ): string {
        $nilai = self::rapikan($nilai);

        if ($nilai !== '' && ctype_digit($nilai)) {
            return self::rapikan(
                $db->table($tabel)
                    ->where($kunci, (int) $nilai)
                    ->value($kolomNama)
            );
        }

        return $nilai;
    }

    private static function rapikan(mixed $teks): string
    {
        $teks = (string) $teks;

        return trim(preg_replace('/\s+/u', ' ', $teks) ?? $teks);
    }
}