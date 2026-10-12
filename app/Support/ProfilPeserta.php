<?php

namespace App\Support;

/**
 * Satu-satunya tempat yang mengatur field profil per jenis peserta.
 *
 * Kebijakan field:
 * - Internal:
 *   Jika SIMPEG aktif, peserta cukup memasukkan NIP.
 *   Nama, Unit Kerja, dan Jabatan diambil otomatis dari SIMPEG.
 *   Nomor Telepon tidak digunakan.
 *
 *   Jika SIMPEG tidak aktif, Internal menggunakan input manual
 *   dengan NIP 18 digit.
 *
 * - Eksternal:
 *   Nama, Instansi/Lembaga, Jabatan, dan Nomor Telepon wajib.
 *   NIK bersifat opsional.
 *
 * - Tamu:
 *   Nama dan Nomor Telepon wajib.
 *   Asal Instansi bersifat opsional.
 */
class ProfilPeserta
{
    public const JENIS = ['internal', 'eksternal', 'tamu'];

    private const POLA = [
        'nip' => [
            '/^[0-9]{18}$/',
            'NIP harus 18 digit angka.',
        ],

        /*
         * Saat terhubung ke SIMPEG, validitas NIP ditentukan
         * berdasarkan data yang benar-benar ada di database kepegawaian.
         *
         * Format dibuat fleksibel 10-20 digit karena data pegawai
         * dapat memiliki panjang nomor yang berbeda.
         */
        'nip_simpeg' => [
            '/^[0-9]{10,20}$/',
            'NIP hanya boleh berisi angka.',
        ],

        'nik' => [
            '/^[0-9]{16}$/',
            'NIK harus 16 digit angka.',
        ],

        'telp' => [
            '/^[0-9+][0-9]{8,14}$/',
            'Nomor telepon hanya boleh angka, 9-15 digit.',
        ],
    ];

    /**
     * Memastikan jenis peserta valid.
     */
    public static function valid(string $jenis): bool
    {
        return in_array($jenis, self::JENIS, true);
    }

    /**
     * Mendefinisikan field berdasarkan jenis peserta.
     *
     * @return array<string, array{
     *     label: string,
     *     required: bool,
     *     rule?: string,
     *     readonly?: bool,
     *     lookup?: bool
     * }>
     */
    public static function fields(string $jenis): array
    {
        return match ($jenis) {
            /*
             * INTERNAL
             *
             * Jika SIMPEG aktif:
             * - NIP wajib diisi.
             * - NIP digunakan untuk mencari data pegawai.
             * - Nama, Unit Kerja, dan Jabatan diisi otomatis.
             * - Field hasil pencarian dibuat readonly.
             * - Nomor Telepon tidak ditampilkan.
             */
            'internal' => Simpeg::aktif()
                ? [
                    'nip_nik' => [
                        'label' => 'NIP',
                        'required' => true,
                        'rule' => 'nip_simpeg',
                        'lookup' => true,
                    ],

                    'nama' => [
                        'label' => 'Nama Lengkap',
                        'required' => false,
                        'readonly' => true,
                    ],

                    'unit_kerja' => [
                        'label' => 'Unit Kerja',
                        'required' => false,
                        'readonly' => true,
                    ],

                    'jabatan' => [
                        'label' => 'Jabatan',
                        'required' => false,
                        'readonly' => true,
                    ],
                ]
                : [
                    'nama' => [
                        'label' => 'Nama Lengkap',
                        'required' => true,
                    ],

                    'nip_nik' => [
                        'label' => 'NIP',
                        'required' => true,
                        'rule' => 'nip',
                    ],

                    'unit_kerja' => [
                        'label' => 'Unit Kerja',
                        'required' => true,
                    ],

                    'jabatan' => [
                        'label' => 'Jabatan',
                        'required' => true,
                    ],
                ],

            /*
             * EKSTERNAL
             */
            'eksternal' => [
                'nama' => [
                    'label' => 'Nama Lengkap',
                    'required' => true,
                ],

                'instansi' => [
                    'label' => 'Instansi / Lembaga',
                    'required' => true,
                ],

                'jabatan' => [
                    'label' => 'Jabatan',
                    'required' => true,
                ],

                'no_telp' => [
                    'label' => 'Nomor Telepon',
                    'required' => true,
                    'rule' => 'telp',
                ],

                'nip_nik' => [
                    'label' => 'NIK',
                    'required' => false,
                    'rule' => 'nik',
                ],
            ],

            /*
             * TAMU
             */
            'tamu' => [
                'nama' => [
                    'label' => 'Nama Lengkap',
                    'required' => true,
                ],

                'instansi' => [
                    'label' => 'Asal Instansi',
                    'required' => false,
                ],

                'no_telp' => [
                    'label' => 'Nomor Telepon',
                    'required' => true,
                    'rule' => 'telp',
                ],
            ],

            default => [],
        };
    }

    /**
     * Membuat aturan validasi berdasarkan field yang aktif.
     *
     * @return array<string, list<string>>
     */
    public static function rules(string $jenis): array
    {
        $rules = [];

        foreach (self::fields($jenis) as $key => $cfg) {
            $r = [
                $cfg['required'] ? 'required' : 'nullable',
                'string',
            ];

            $rule = $cfg['rule'] ?? null;

            if ($rule !== null && isset(self::POLA[$rule])) {
                $r[] = 'regex:'.self::POLA[$rule][0];
            } else {
                $r[] = 'max:150';
            }

            $rules[$key] = $r;
        }

        return $rules;
    }

    /**
     * Nama atribut yang digunakan Laravel pada pesan validasi.
     *
     * @return array<string, string>
     */
    public static function attributes(string $jenis): array
    {
        return array_map(
            fn ($cfg) => $cfg['label'],
            self::fields($jenis)
        );
    }

    /**
     * Pesan validasi khusus berdasarkan pola field.
     *
     * @return array<string, string>
     */
    public static function messages(string $jenis): array
    {
        $messages = [];

        foreach (self::fields($jenis) as $key => $cfg) {
            $rule = $cfg['rule'] ?? null;

            if ($rule !== null && isset(self::POLA[$rule])) {
                $messages["{$key}.regex"] = self::POLA[$rule][1];
            }
        }

        return $messages;
    }

    /**
     * Sidik jari pengenal peserta untuk mencegah absensi ganda
     * pada satu kegiatan.
     *
     * Internal:
     *   menggunakan NIP.
     *
     * Eksternal/Tamu:
     *   menggunakan nomor telepon.
     *
     * HMAC menggunakan APP_KEY sehingga nilai asli tidak dapat
     * ditebak hanya dari hasil hash.
     */
    public static function kunciUnik(string $jenis, array $data): string
    {
        if ($jenis === 'internal') {
            $pengenal = 'nip:'.preg_replace(
                '/\D+/',
                '',
                (string) ($data['nip_nik'] ?? '')
            );
        } else {
            $telp = preg_replace(
                '/\D+/',
                '',
                (string) ($data['no_telp'] ?? '')
            );

            /*
             * Menyamakan format:
             * +62812xxxx
             * 62812xxxx
             * 0812xxxx
             *
             * menjadi:
             * 0812xxxx
             */
            if (str_starts_with($telp, '62')) {
                $telp = '0'.substr($telp, 2);
            }

            $pengenal = 'tel:'.$telp;
        }

        return hash_hmac(
            'sha256',
            $pengenal,
            (string) config('app.key')
        );
    }
}