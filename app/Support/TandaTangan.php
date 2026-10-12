<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Memvalidasi dan membersihkan tanda tangan kiriman browser (data URL PNG).
 *
 * Browser TIDAK dipercaya: data diurai ulang di server, dipastikan benar-benar PNG
 * berukuran wajar, tidak kosong, lalu di-encode ulang dengan GD sehingga byte tambahan
 * (mis. payload tersembunyi) terbuang. Hasilnya disimpan sebagai berkas, bukan teks base64.
 */
class TandaTangan
{
    private const PREFIX = 'data:image/png;base64,';

    private const MAKS_PANJANG_DATA_URL = 600_000;

    private const MAKS_LEBAR = 2400;

    private const MAKS_TINGGI = 1200;

    /** Jumlah titik berwarna minimum agar goresan dianggap ada (bukan kanvas kosong). */
    private const MIN_TITIK_GORESAN = 30;

    public static function dekode(mixed $dataUrl): string
    {
        if (! is_string($dataUrl)
            || strlen($dataUrl) > self::MAKS_PANJANG_DATA_URL
            || ! str_starts_with($dataUrl, self::PREFIX)) {
            throw self::gagal();
        }

        $biner = base64_decode(substr($dataUrl, strlen(self::PREFIX)), true);
        if ($biner === false || strlen($biner) < 100) {
            throw self::gagal();
        }

        $info = @getimagesizefromstring($biner);
        if ($info === false
            || $info[2] !== IMAGETYPE_PNG
            || $info[0] < 50 || $info[1] < 20
            || $info[0] > self::MAKS_LEBAR || $info[1] > self::MAKS_TINGGI) {
            throw self::gagal();
        }

        $gambar = @imagecreatefromstring($biner);
        if ($gambar === false) {
            throw self::gagal();
        }

        if (! imageistruecolor($gambar)) {
            imagepalettetotruecolor($gambar);
        }

        if (self::hitungTitikGoresan($gambar, $info[0], $info[1]) < self::MIN_TITIK_GORESAN) {
            imagedestroy($gambar);
            throw self::gagal('Tanda tangan masih kosong. Bubuhkan tanda tangan pada kotak yang tersedia.');
        }

        imagesavealpha($gambar, true);
        ob_start();
        imagepng($gambar);
        $bersih = (string) ob_get_clean();
        imagedestroy($gambar);

        if ($bersih === '') {
            throw self::gagal();
        }

        return $bersih;
    }

    public static function dataUri(string $png): string
    {
        return self::PREFIX.base64_encode($png);
    }

    private static function hitungTitikGoresan(\GdImage $gambar, int $lebar, int $tinggi): int
    {
        $titik = 0;

        for ($y = 0; $y < $tinggi; $y += 2) {
            for ($x = 0; $x < $lebar; $x += 2) {
                $alpha = (imagecolorat($gambar, $x, $y) >> 24) & 0x7F; // 127 = transparan penuh
                if ($alpha < 100) {
                    $titik++;
                    if ($titik >= self::MIN_TITIK_GORESAN) {
                        return $titik;
                    }
                }
            }
        }

        return $titik;
    }

    private static function gagal(string $pesan = 'Tanda tangan tidak valid. Silakan bubuhkan tanda tangan ulang.'): ValidationException
    {
        return ValidationException::withMessages(['tanda_tangan' => $pesan]);
    }
}
