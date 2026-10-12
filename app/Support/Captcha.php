<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Captcha gambar buatan sendiri (GD). Sengaja tidak memakai layanan pihak ketiga
 * (reCAPTCHA/Turnstile) supaya CSP tetap ketat dan tidak ada data peserta yang
 * keluar ke pihak lain. Jawaban hanya disimpan sebagai HMAC di sesi, berlaku sekali pakai.
 */
class Captcha
{
    private const SESI = 'captcha_hash';

    private const HURUF = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa I, O, 0, 1 agar tidak membingungkan

    private const PANJANG = 5;

    public static function kode(): string
    {
        $kode = '';
        for ($i = 0; $i < self::PANJANG; $i++) {
            $kode .= self::HURUF[random_int(0, strlen(self::HURUF) - 1)];
        }

        return $kode;
    }

    public static function hash(string $kode): string
    {
        return hash_hmac('sha256', strtoupper(trim($kode)), (string) config('app.key'));
    }

    /** Membuat kode baru, menyimpan hash-nya di sesi, dan mengembalikan PNG-nya. */
    public static function terbitkan(): string
    {
        $kode = self::kode();
        Session::put(self::SESI, self::hash($kode));

        return self::gambar($kode);
    }

    /** Jawaban hanya bisa dicoba satu kali: kode dibuang sebelum dibandingkan. */
    public static function cocok(?string $jawaban): bool
    {
        $hash = Session::pull(self::SESI);

        if (! is_string($hash) || ! is_string($jawaban) || $jawaban === '') {
            return false;
        }

        return hash_equals($hash, self::hash($jawaban));
    }

    public static function gambar(string $kode): string
    {
        $lebar = 190;
        $tinggi = 64;
        $im = imagecreatetruecolor($lebar, $tinggi);
        imagefill($im, 0, 0, imagecolorallocate($im, 247, 243, 238));

        // Noise: titik dan garis berwarna acak.
        for ($i = 0; $i < 260; $i++) {
            imagesetpixel($im, random_int(0, $lebar - 1), random_int(0, $tinggi - 1),
                imagecolorallocate($im, random_int(150, 220), random_int(150, 220), random_int(150, 220)));
        }
        for ($i = 0; $i < 5; $i++) {
            imageline($im, random_int(0, $lebar), random_int(0, $tinggi), random_int(0, $lebar), random_int(0, $tinggi),
                imagecolorallocate($im, random_int(120, 200), random_int(120, 200), random_int(120, 200)));
        }

        // Tiap huruf digambar kecil, diperbesar, diputar sedikit, lalu ditempel di posisi acak.
        $x = 12;
        foreach (str_split($kode) as $huruf) {
            $kecil = imagecreatetruecolor(12, 18);
            $bg = imagecolorallocate($kecil, 247, 243, 238);
            imagefill($kecil, 0, 0, $bg);
            imagestring($kecil, 5, 2, 1, $huruf, imagecolorallocate($kecil, random_int(20, 110), random_int(20, 60), random_int(40, 110)));

            $besar = imagecreatetruecolor(34, 50);
            imagecopyresized($besar, $kecil, 0, 0, 0, 0, 34, 50, 12, 18);
            $putar = imagerotate($besar, random_int(-22, 22), imagecolorallocate($besar, 247, 243, 238));
            if ($putar !== false) {
                imagecopymerge($im, $putar, $x, random_int(0, 8), 0, 0, min(imagesx($putar), 38), min(imagesy($putar), 54), 100);
                imagedestroy($putar);
            }
            imagedestroy($kecil);
            imagedestroy($besar);
            $x += 34;
        }

        // Garis coretan di atas huruf.
        for ($i = 0; $i < 2; $i++) {
            imageline($im, 0, random_int(10, $tinggi - 10), $lebar, random_int(10, $tinggi - 10),
                imagecolorallocate($im, random_int(60, 140), random_int(60, 140), random_int(60, 140)));
        }

        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png;
    }
}
