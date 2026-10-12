<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Notifications\SalinanAbsensiNotification;
use App\Support\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatTandaTangan;
use Tests\TestCase;

class AbsenTest extends TestCase
{
    use MembuatTandaTangan;
    use RefreshDatabase;

    private const KODE = 'ABCDE';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Menyiapkan captcha di sesi lalu mengirim form seperti peserta sungguhan. */
    private function kirim(Kegiatan $k, string $jenis, array $data = [], string $kodeCaptcha = self::KODE)
    {
        return $this->withSession(['captcha_hash' => Captcha::hash(self::KODE)])
            ->post(route('absen.submit', [$k, $jenis]), array_merge($this->dasar($jenis), ['captcha' => $kodeCaptcha], $data));
    }

    private function dasar(string $jenis): array
    {
        $umum = [
            '_t' => Crypt::encryptString((string) now()->subMinute()->timestamp),
            'tanda_tangan' => $this->tandaTanganValid(),
            'konfirmasi' => '1',
        ];

        return $umum + match ($jenis) {
            'internal' => ['nama' => 'Siti Pegawai', 'nip_nik' => '198001012005012001', 'unit_kerja' => 'Biro Umum', 'jabatan' => 'Analis', 'tipe_peserta' => 'Peserta'],
            'eksternal' => ['nama' => 'Rudi Mitra', 'instansi' => 'LSM Contoh', 'jabatan' => 'Direktur', 'no_telp' => '081234567890', 'tipe_peserta' => 'Narasumber'],
            default => ['nama' => 'Budi Tamu', 'no_telp' => '081200000001'],
        };
    }

    public function test_peserta_absen_tanpa_login_dan_berkas_tanda_tangan_tersimpan(): void
    {
        $k = Kegiatan::factory()->create();

        $this->assertGuest();
        $this->kirim($k, 'tamu')->assertOk()->assertSee('kehadiran Anda tercatat');

        $baris = Kehadiran::firstOrFail();
        $this->assertSame('Budi Tamu', $baris->nama);
        Storage::disk('local')->assertExists($baris->tanda_tangan_path);
        $this->assertStringStartsWith("\x89PNG", Storage::disk('local')->get($baris->tanda_tangan_path));
    }

    public function test_ketiga_jenis_peserta_bisa_absen(): void
    {
        $k = Kegiatan::factory()->create();

        foreach (['internal', 'eksternal', 'tamu'] as $jenis) {
            $this->kirim($k, $jenis)->assertOk()->assertSee('kehadiran Anda tercatat');
        }

        $this->assertDatabaseCount('kehadiran', 3);
    }

    public function test_absen_ganda_ditolak_berdasarkan_nip_atau_nomor_telepon(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'internal')->assertSee('kehadiran Anda tercatat');
        // NIP sama, nama dibeda-bedakan: tetap dianggap orang yang sama.
        $this->kirim($k, 'internal', ['nama' => 'Nama Lain'])->assertSee('sudah tercatat hadir');

        $this->kirim($k, 'tamu', ['no_telp' => '081200000001'])->assertSee('kehadiran Anda tercatat');
        // Format +62 dan 0 dianggap nomor yang sama.
        $this->kirim($k, 'tamu', ['no_telp' => '+6281200000001'])->assertSee('sudah tercatat hadir');

        $this->assertDatabaseCount('kehadiran', 2);
    }

    public function test_orang_yang_sama_boleh_absen_di_kegiatan_berbeda(): void
    {
        $a = Kegiatan::factory()->create();
        $b = Kegiatan::factory()->create();

        $this->kirim($a, 'internal');
        $this->kirim($b, 'internal')->assertSee('kehadiran Anda tercatat');

        $this->assertDatabaseCount('kehadiran', 2);
    }

    public function test_unique_index_database_menolak_baris_ganda(): void
    {
        $k = Kegiatan::factory()->create();
        $this->kirim($k, 'tamu');
        $kunci = Kehadiran::firstOrFail()->makeVisible('kunci_unik')->kunci_unik;

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Kehadiran::create([
            'kegiatan_id' => $k->id, 'jenis_peserta' => 'tamu', 'nama' => 'Dobel',
            'kunci_unik' => $kunci, 'tanda_tangan_path' => 'tanda-tangan/x.png',
        ]);
    }

    public function test_captcha_salah_atau_kosong_ditolak_dan_tidak_menyimpan_apa_pun(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'tamu', [], 'ZZZZZ')->assertSessionHasErrors('captcha');
        $this->kirim($k, 'tamu', ['captcha' => ''])->assertSessionHasErrors('captcha');

        $this->assertDatabaseCount('kehadiran', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_captcha_hanya_berlaku_sekali(): void
    {
        $k = Kegiatan::factory()->create();

        // Tanpa captcha baru di sesi (sudah terpakai), kode yang sama tidak boleh dipakai ulang.
        $this->kirim($k, 'internal')->assertSee('kehadiran Anda tercatat');
        $this->post(route('absen.submit', [$k, 'tamu']), array_merge($this->dasar('tamu'), ['captcha' => self::KODE]))
            ->assertSessionHasErrors('captcha');
    }

    public function test_endpoint_captcha_mengeluarkan_png_dan_tidak_di_cache(): void
    {
        $response = $this->get(route('captcha'));

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
        $this->assertNotNull(session('captcha_hash'));
    }

    public function test_honeypot_terisi_dianggap_bot_dan_tidak_menyimpan(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'tamu', ['website' => 'http://spam.test'])->assertOk()->assertSee('kehadiran Anda tercatat');

        $this->assertDatabaseCount('kehadiran', 0);
    }

    public function test_formulir_yang_dikirim_terlalu_cepat_atau_dengan_token_palsu_ditolak(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'tamu', ['_t' => Crypt::encryptString((string) now()->timestamp)])->assertSessionHasErrors('captcha');
        $this->kirim($k, 'tamu', ['_t' => 'token-palsu'])->assertSessionHasErrors('captcha');
        $this->kirim($k, 'tamu', ['_t' => Crypt::encryptString((string) now()->subHours(5)->timestamp)])->assertSessionHasErrors('captcha');

        $this->assertDatabaseCount('kehadiran', 0);
    }

    public function test_kegiatan_ditutup_belum_mulai_dan_sudah_lewat_ditolak(): void
    {
        $ditutup = Kegiatan::factory()->create(['status' => 'ditutup']);
        $belum = Kegiatan::factory()->create(['mulai_at' => now()->addDay()]);
        $lewat = Kegiatan::factory()->create(['selesai_at' => now()->subDay()]);

        foreach ([$ditutup, $belum, $lewat] as $k) {
            $this->get(route('absen.jenis', $k))->assertForbidden();
            $this->get(route('absen.form', [$k, 'tamu']))->assertForbidden();
            $this->kirim($k, 'tamu')->assertForbidden();
        }

        $this->assertDatabaseCount('kehadiran', 0);
    }

    public function test_daftar_kegiatan_hanya_menampilkan_yang_sedang_buka(): void
    {
        Kegiatan::factory()->create(['nama' => 'Sedang Buka']);
        Kegiatan::factory()->create(['nama' => 'Sudah Ditutup', 'status' => 'ditutup']);
        Kegiatan::factory()->create(['nama' => 'Belum Mulai', 'mulai_at' => now()->addHour()]);
        Kegiatan::factory()->create(['nama' => 'Sudah Lewat', 'selesai_at' => now()->subHour()]);

        $this->get(route('absen.index'))->assertOk()
            ->assertSee('Sedang Buka')
            ->assertDontSee('Sudah Ditutup')->assertDontSee('Belum Mulai')->assertDontSee('Sudah Lewat');
    }

    public function test_jenis_peserta_tidak_dikenal_ditolak(): void
    {
        $k = Kegiatan::factory()->create();

        $this->get(route('absen.form', [$k, 'admin']))->assertNotFound();
        $this->post(route('absen.submit', [$k, 'superadmin']), [])->assertNotFound();
    }

    public function test_tanda_tangan_kosong_atau_palsu_ditolak(): void
    {
        $k = Kegiatan::factory()->create();

        $curang = [
            'kosong' => $this->tandaTanganKosong(),
            'bukan_data_url' => 'halo',
            'php_berlabel_png' => 'data:image/png;base64,'.base64_encode('<?php system($_GET["x"]); ?>'.str_repeat('A', 200)),
            'svg' => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'jpeg_berlabel_png' => 'data:image/png;base64,'.base64_encode("\xFF\xD8\xFF\xE0".str_repeat("\0", 300)),
            'base64_rusak' => 'data:image/png;base64,@@@@@@',
            'terlalu_besar' => 'data:image/png;base64,'.str_repeat('A', 700_000),
        ];

        foreach ($curang as $nama => $nilai) {
            $this->kirim($k, 'tamu', ['tanda_tangan' => $nilai]);
            $this->assertTrue(session('errors')?->has('tanda_tangan') ?? false, "Seharusnya ditolak: {$nama}");
        }

        $this->assertDatabaseCount('kehadiran', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_konfirmasi_wajib_dicentang(): void
    {
        $this->kirim(Kegiatan::factory()->create(), 'tamu', ['konfirmasi' => null])->assertSessionHasErrors('konfirmasi');
    }

    public function test_validasi_isian_sesuai_jenis(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'internal', ['nip_nik' => '12345'])->assertSessionHasErrors('nip_nik');
        $this->kirim($k, 'internal', ['unit_kerja' => ''])->assertSessionHasErrors('unit_kerja');
        $this->kirim($k, 'tamu', ['no_telp' => 'abc'])->assertSessionHasErrors('no_telp');
        $this->kirim($k, 'eksternal', ['nip_nik' => '123'])->assertSessionHasErrors('nip_nik');
        $this->kirim($k, 'tamu', ['nama' => str_repeat('x', 200)])->assertSessionHasErrors('nama');
    }

    public function test_tipe_kehadiran_hanya_untuk_internal_dan_eksternal(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'internal', ['tipe_peserta' => 'Hacker'])->assertSessionHasErrors('tipe_peserta');
        $this->kirim($k, 'tamu', ['tipe_peserta' => 'Narasumber'])->assertSessionHasErrors('tipe_peserta');
        $this->kirim($k, 'internal', ['tipe_peserta' => 'Narasumber'])->assertOk();

        $this->assertSame('Narasumber', Kehadiran::firstOrFail()->tipe_peserta);
    }

    public function test_nip_dan_telepon_tersimpan_terenkripsi_dan_hash_tidak_membocorkan_nomor(): void
    {
        $k = Kegiatan::factory()->create();
        $this->kirim($k, 'internal', ['no_telp' => '081299998888']);

        $mentah = DB::table('kehadiran')->first();
        $this->assertNotSame('198001012005012001', $mentah->nip_nik);
        $this->assertNotSame('081299998888', $mentah->no_telp);
        $this->assertStringNotContainsString('198001012005012001', $mentah->kunci_unik);
        $this->assertSame('198001012005012001', Kehadiran::first()->nip_nik);
    }

    public function test_isian_publik_dibersihkan_dari_karakter_kontrol(): void
    {
        $k = Kegiatan::factory()->create();
        $this->kirim($k, 'tamu', ['nama' => "Budi\x00\x07 Tamu\n"]);

        $this->assertSame('Budi Tamu', Kehadiran::firstOrFail()->nama);
    }

    public function test_salinan_email_dikirim_jika_dicentang_dan_alamatnya_tidak_disimpan(): void
    {
        Notification::fake();
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'tamu', ['kirim_salinan' => '1', 'email_salinan' => 'Budi@Example.TEST'])
            ->assertOk()->assertSee('Salinan jawaban sudah dikirim');

        Notification::assertSentOnDemand(SalinanAbsensiNotification::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'budi@example.test');
        $this->assertStringNotContainsString('budi@example.test', json_encode(DB::table('kehadiran')->get()));
    }

    public function test_salinan_email_tidak_dikirim_jika_tidak_dicentang(): void
    {
        Notification::fake();
        $this->kirim(Kegiatan::factory()->create(), 'tamu', ['email_salinan' => 'budi@example.test'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_email_wajib_dan_valid_jika_salinan_dicentang(): void
    {
        $k = Kegiatan::factory()->create();

        $this->kirim($k, 'tamu', ['kirim_salinan' => '1', 'email_salinan' => ''])->assertSessionHasErrors('email_salinan');
        $this->kirim($k, 'tamu', ['kirim_salinan' => '1', 'email_salinan' => 'bukan-email'])->assertSessionHasErrors('email_salinan');
    }

    public function test_pengiriman_salinan_dibatasi_agar_tidak_jadi_alat_spam(): void
    {
        Notification::fake();
        $k = Kegiatan::factory()->create();
        RateLimiter::clear('salinan-email:'.sha1('korban@example.test'));

        foreach (range(1, 5) as $i) {
            $this->kirim($k, 'tamu', ['no_telp' => '08120000010'.$i, 'kirim_salinan' => '1', 'email_salinan' => 'korban@example.test']);
        }

        // Alamat yang sama hanya menerima 3 email per jam.
        Notification::assertSentOnDemandTimes(SalinanAbsensiNotification::class, 3);
        // Absensinya sendiri tetap tercatat semua.
        $this->assertDatabaseCount('kehadiran', 5);
    }

    public function test_kegagalan_smtp_tidak_membatalkan_absensi(): void
    {
        $k = Kegiatan::factory()->create();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->kirim($k, 'tamu', ['kirim_salinan' => '1', 'email_salinan' => 'budi@example.test'])
            ->assertOk()->assertSee('kehadiran Anda tercatat')->assertDontSee('Salinan jawaban sudah dikirim');

        $this->assertDatabaseCount('kehadiran', 1);
    }

    public function test_form_berisi_elemen_yang_diminta_checkbox_email_captcha_reset_submit(): void
    {
        $k = Kegiatan::factory()->create();

        $this->get(route('absen.form', [$k, 'tamu']))->assertOk()
            ->assertSee('Kirim salinan jawaban ini ke alamat email di bawah.')
            ->assertSee('name="email_salinan"', false)
            ->assertSee('id="captchaImg"', false)
            ->assertSee('type="reset"', false)
            ->assertSee('type="submit"', false);
    }
}
