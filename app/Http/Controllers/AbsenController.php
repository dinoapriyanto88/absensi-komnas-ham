<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Notifications\SalinanAbsensiNotification;
use App\Support\Captcha;
use App\Support\ProfilPeserta;
use App\Support\Simpeg;
use App\Support\TandaTangan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Halaman absensi PUBLIK (tanpa login).
 *
 * Peserta tidak dapat melihat daftar seluruh kegiatan.
 * Peserta hanya dapat mengakses kegiatan melalui tautan presensi
 * yang diberikan oleh Admin.
 *
 * Pengamanan:
 * - CAPTCHA
 * - honeypot
 * - batas minimum pengisian
 * - batas maksimum waktu form
 * - throttle
 * - validasi input
 * - validasi tanda tangan
 * - unique index anti-ganda
 * - pencarian NIP yang dibatasi
 */
class AbsenController extends Controller
{
    /** Form yang dikirim lebih cepat dari ini hampir pasti bot. */
    private const MIN_DETIK_ISI = 4;

    /** Form yang dibiarkan terbuka lebih lama dari ini harus dimuat ulang. */
    private const MAKS_MENIT_ISI = 180;

    /** Batas pengiriman salinan email per IP per jam. */
    private const MAKS_SALINAN_EMAIL_PER_JAM = 5;

    /**
     * Pencarian NIP yang tidak ditemukan dibatasi per IP.
     * Tujuannya mencegah penebakan NIP satu per satu.
     */
    private const MAKS_NIP_TIDAK_DITEMUKAN = 20;

    /**
     * Halaman informasi publik.
     *
     * Peserta tidak melihat daftar kegiatan.
     * Peserta diarahkan menggunakan tautan kegiatan dari penyelenggara.
     */
    public function index(): View
    {
        return view('absen.info');
    }

    /**
     * Halaman pemilihan jenis peserta untuk kegiatan tertentu.
     */
    public function pilihJenis(Kegiatan $kegiatan): View
    {
        $this->pastikanTerbuka($kegiatan);

        return view('absen.pilih-jenis', [
            'kegiatan' => $kegiatan,
        ]);
    }

    /**
     * Form absensi berdasarkan kegiatan dan jenis peserta.
     */
    public function form(Kegiatan $kegiatan, string $jenis): View
    {
        $this->pastikanTerbuka($kegiatan);

        abort_unless(
            ProfilPeserta::valid($jenis),
            404
        );

        return view('absen.isi-form', [
            'kegiatan' => $kegiatan,
            'jenis' => $jenis,
            'fields' => ProfilPeserta::fields($jenis),
            'punyaTipe' => in_array(
                $jenis,
                ['internal', 'eksternal'],
                true
            ),
            'token' => Crypt::encryptString(
                (string) now()->timestamp
            ),
        ]);
    }

    /**
     * Pencarian data pegawai berdasarkan NIP untuk peserta Internal.
     *
     * Nama, jabatan, dan unit kerja diambil dari database SIMPEG.
     * Tidak ada data pribadi lain yang dikembalikan.
     */
    public function cariNip(
        Request $request,
        Kegiatan $kegiatan
    ): JsonResponse {
        $this->pastikanTerbuka($kegiatan);

        abort_unless(
            Simpeg::aktif(),
            404
        );

        $nip = preg_replace(
            '/\D+/',
            '',
            (string) $request->input('nip_nik')
        ) ?? '';

        if (! preg_match('/^[0-9]{10,20}$/', $nip)) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Masukkan NIP berupa angka.',
            ], 422);
        }

        $kunci = $this->kunciNipTidakDitemukan($request);

        if (
            RateLimiter::tooManyAttempts(
                $kunci,
                self::MAKS_NIP_TIDAK_DITEMUKAN
            )
        ) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Terlalu banyak pencarian yang tidak ditemukan. Coba lagi beberapa menit lagi.',
            ], 429);
        }

        $hasil = Simpeg::cari($nip);

        if ($hasil['status'] === Simpeg::OK) {
            return response()->json([
                'ok' => true,
                'data' => $hasil['data'],
            ]);
        }

        if ($hasil['status'] !== Simpeg::ERROR) {
            RateLimiter::hit($kunci, 600);
        }

        return response()->json([
            'ok' => false,
            'pesan' => Simpeg::pesan($hasil['status']),
        ], Simpeg::kodeHttp($hasil['status']));
    }

    /**
     * Menyimpan presensi peserta.
     */
    public function submit(
        Request $request,
        Kegiatan $kegiatan,
        string $jenis
    ): View {
        $this->pastikanTerbuka($kegiatan);

        abort_unless(
            ProfilPeserta::valid($jenis),
            404
        );

        /*
         * Honeypot.
         *
         * Kolom ini disembunyikan dari manusia.
         * Jika terisi, anggap sebagai bot dan tampilkan halaman sukses palsu.
         */
        if ($request->filled('website')) {
            return view('absen.selesai', [
                'kegiatan' => $kegiatan,
                'salinanDikirim' => false,
            ]);
        }

        $this->periksaWaktuIsi(
            $request->input('_t')
        );

        $punyaTipe = in_array(
            $jenis,
            ['internal', 'eksternal'],
            true
        );

        /*
         * Email salinan hanya digunakan untuk pengiriman.
         * Email tidak disimpan sebagai data kehadiran.
         */
        $request->merge([
            'email_salinan' => Str::lower(
                trim(
                    (string) $request->input('email_salinan')
                )
            ),
        ]);

        /*
         * Untuk peserta Internal, NIP dibersihkan terlebih dahulu.
         * Jika SIMPEG aktif, hanya angka yang digunakan.
         */
        if (
            $jenis === 'internal'
            && Simpeg::aktif()
        ) {
            $request->merge([
                'nip_nik' => preg_replace(
                    '/\D+/',
                    '',
                    (string) $request->input('nip_nik')
                ),
            ]);
        }

        $data = $request->validate(
            ProfilPeserta::rules($jenis) + [
                'tipe_peserta' => $punyaTipe
                    ? [
                        'required',
                        Rule::in([
                            'Peserta',
                            'Narasumber',
                        ]),
                    ]
                    : [
                        'prohibited',
                    ],

                'tanda_tangan' => [
                    'required',
                    'string',
                ],

                'konfirmasi' => [
                    'accepted',
                ],

                'kirim_salinan' => [
                    'nullable',
                    'boolean',
                ],

                'email_salinan' => [
                    'nullable',
                    'required_if:kirim_salinan,1',
                    'email:rfc',
                    'max:150',
                ],

                'captcha' => [
                    'required',
                    'string',
                    'max:10',
                ],
            ],
            ProfilPeserta::messages($jenis) + [
                'tanda_tangan.required' =>
                    'Tanda tangan wajib diisi.',

                'konfirmasi.accepted' =>
                    'Anda harus mencentang kotak konfirmasi sebelum mengirim.',

                'tipe_peserta.prohibited' =>
                    'Tipe kehadiran tidak berlaku untuk jenis peserta ini.',

                'email_salinan.required_if' =>
                    'Isi alamat email jika ingin menerima salinan.',

                'captcha.required' =>
                    'Ketik huruf pada gambar untuk membuktikan Anda bukan robot.',
            ],
            ProfilPeserta::attributes($jenis) + [
                'email_salinan' => 'Email',
                'captcha' => 'Captcha',
            ]
        );

        /*
         * CAPTCHA diperiksa setelah validasi input.
         * Kode CAPTCHA hanya sekali pakai.
         */
        if (! Captcha::cocok($data['captcha'])) {
            throw ValidationException::withMessages([
                'captcha' =>
                    'Huruf pada gambar tidak cocok. Silakan coba lagi.',
            ]);
        }

        /*
         * Peserta Internal:
         *
         * Nama, jabatan, dan unit kerja TIDAK dipercaya dari input browser.
         * Data diambil ulang dari SIMPEG berdasarkan NIP.
         */
        if (
            $jenis === 'internal'
            && Simpeg::aktif()
        ) {
            $data = $this->terapkanDataPegawai(
                $request,
                $data
            );
        }

        /*
         * Validasi dan decode tanda tangan.
         */
        $png = TandaTangan::dekode(
            $data['tanda_tangan']
        );

        /*
         * Ambil hanya field yang memang diperbolehkan
         * untuk jenis peserta tersebut.
         */
        $profil = Arr::only(
            $data,
            array_keys(
                ProfilPeserta::fields($jenis)
            )
        );

        /*
         * Simpan tanda tangan dengan nama acak.
         */
        $path = 'tanda-tangan/'.Str::uuid().'.png';

        Storage::disk('local')->put(
            $path,
            $png
        );

        try {
            $kehadiran = Kehadiran::create([
                'kegiatan_id' => $kegiatan->id,

                'jenis_peserta' => $jenis,

                'tipe_peserta' => $punyaTipe
                    ? $data['tipe_peserta']
                    : null,

                'kunci_unik' => ProfilPeserta::kunciUnik(
                    $jenis,
                    $profil
                ),

                'tanda_tangan_path' => $path,
            ] + $this->bersihkan($profil));

        } catch (UniqueConstraintViolationException) {

            /*
             * Jika peserta sudah pernah melakukan presensi,
             * hapus tanda tangan yang sempat dibuat.
             */
            Storage::disk('local')->delete($path);

            return view('absen.sudah-absen', [
                'kegiatan' => $kegiatan,
            ]);
        }

        /*
         * Kirim salinan jika peserta mencentang opsi tersebut.
         */
        $salinan =
            ! empty($data['kirim_salinan'])
            && ! empty($data['email_salinan'])
            && $this->kirimSalinan(
                $request,
                $kegiatan,
                $kehadiran,
                $data['email_salinan']
            );

        return view('absen.selesai', [
            'kegiatan' => $kegiatan,
            'salinanDikirim' => $salinan,
        ]);
    }

    /**
     * Mengambil data pegawai dari SIMPEG dan menerapkannya
     * ke data presensi.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function terapkanDataPegawai(
        Request $request,
        array $data
    ): array {
        $kunci = $this->kunciNipTidakDitemukan(
            $request
        );

        if (
            RateLimiter::tooManyAttempts(
                $kunci,
                self::MAKS_NIP_TIDAK_DITEMUKAN
            )
        ) {
            throw ValidationException::withMessages([
                'nip_nik' =>
                    'Terlalu banyak pencarian yang tidak ditemukan. Coba lagi beberapa menit lagi.',
            ]);
        }

        $hasil = Simpeg::cari(
            (string) $data['nip_nik']
        );

        if ($hasil['status'] !== Simpeg::OK) {

            if ($hasil['status'] !== Simpeg::ERROR) {
                RateLimiter::hit(
                    $kunci,
                    600
                );
            }

            throw ValidationException::withMessages([
                'nip_nik' =>
                    Simpeg::pesan(
                        $hasil['status']
                    ),
            ]);
        }

        return array_merge(
            $data,
            $hasil['data'],
            [
                'nip_nik' => preg_replace(
                    '/\D+/',
                    '',
                    (string) $data['nip_nik']
                ),
            ]
        );
    }

    /**
     * Kunci rate limiter pencarian NIP berdasarkan IP.
     */
    private function kunciNipTidakDitemukan(
        Request $request
    ): string {
        return 'cari-nip-gagal:'.$request->ip();
    }

    /**
     * Alamat email tidak disimpan ke database.
     * Hanya digunakan sekali untuk mengirim salinan presensi.
     */
    private function kirimSalinan(
        Request $request,
        Kegiatan $kegiatan,
        Kehadiran $kehadiran,
        string $email
    ): bool {
        $kunciIp =
            'salinan-ip:'.$request->ip();

        $kunciEmail =
            'salinan-email:'.sha1($email);

        /*
         * Batas pengiriman:
         * - maksimal 5 kali per IP per jam
         * - maksimal 3 kali per alamat email per jam
         */
        if (
            RateLimiter::tooManyAttempts(
                $kunciIp,
                self::MAKS_SALINAN_EMAIL_PER_JAM
            )
            ||
            RateLimiter::tooManyAttempts(
                $kunciEmail,
                3
            )
        ) {
            return false;
        }

        RateLimiter::hit(
            $kunciIp,
            3600
        );

        RateLimiter::hit(
            $kunciEmail,
            3600
        );

        try {
            Notification::route(
                'mail',
                $email
            )->notify(
                new SalinanAbsensiNotification(
                    $kegiatan,
                    $kehadiran
                )
            );

            return true;

        } catch (\Throwable $e) {

            /*
             * Absensi sudah tersimpan.
             * Kegagalan email tidak membatalkan presensi.
             */
            report($e);

            return false;
        }
    }

    /**
     * Memeriksa umur token form.
     */
    private function periksaWaktuIsi(
        mixed $token
    ): void {
        try {
            $dibuka = (int) Crypt::decryptString(
                (string) $token
            );

        } catch (\Throwable) {

            throw ValidationException::withMessages([
                'captcha' =>
                    'Formulir kedaluwarsa. Muat ulang halaman lalu isi kembali.',
            ]);
        }

        $selisih =
            now()->timestamp - $dibuka;

        if (
            $selisih < self::MIN_DETIK_ISI
        ) {
            throw ValidationException::withMessages([
                'captcha' =>
                    'Formulir dikirim terlalu cepat. Silakan coba lagi.',
            ]);
        }

        if (
            $selisih >
            self::MAKS_MENIT_ISI * 60
        ) {
            throw ValidationException::withMessages([
                'captcha' =>
                    'Formulir sudah terlalu lama terbuka. Muat ulang halaman lalu isi kembali.',
            ]);
        }
    }

    /**
     * Membersihkan karakter kontrol dan spasi berlebih
     * dari input peserta.
     *
     * @param array<string, mixed> $profil
     * @return array<string, mixed>
     */
    private function bersihkan(
        array $profil
    ): array {
        return array_map(
            function ($nilai) {
                $tanpaKontrol =
                    preg_replace(
                        '/[\x00-\x1F\x7F]+/u',
                        ' ',
                        (string) $nilai
                    ) ?? '';

                $teks =
                    trim(
                        preg_replace(
                            '/\s+/u',
                            ' ',
                            $tanpaKontrol
                        ) ?? ''
                    );

                return $teks === ''
                    ? null
                    : $teks;
            },
            $profil
        );
    }

    /**
     * Memastikan kegiatan masih dapat menerima presensi.
     */
    private function pastikanTerbuka(
        Kegiatan $kegiatan
    ): void {
        $jendela =
            $kegiatan->jendela();

        if ($jendela['buka']) {
            return;
        }

        $nama =
            $kegiatan->nama;

        abort(
            403,
            match ($jendela['alasan']) {

                'belum_mulai' =>
                    "Absensi untuk kegiatan \"{$nama}\" baru dibuka mulai "
                    .$jendela['waktu']
                        ->translatedFormat('j F Y, H:i')
                    .'.',

                'sudah_lewat' =>
                    "Absensi untuk kegiatan \"{$nama}\" ditutup sejak "
                    .$jendela['waktu']
                        ->translatedFormat('j F Y, H:i')
                    .'.',

                default =>
                    "Absensi untuk kegiatan \"{$nama}\" sudah ditutup oleh admin.",
            }
        );
    }
}