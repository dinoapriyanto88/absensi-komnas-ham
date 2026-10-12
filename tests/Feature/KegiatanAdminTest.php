<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KegiatanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function kehadiranUntuk(Kegiatan $kegiatan, array $override = []): Kehadiran
    {
        return Kehadiran::create(array_merge([
            'kegiatan_id' => $kegiatan->id,
            'jenis_peserta' => 'tamu',
            'nama' => 'Peserta Contoh',
            'kunci_unik' => bin2hex(random_bytes(16)),
            'tanda_tangan_path' => 'tanda-tangan/contoh.png',
        ], $override));
    }

    public function test_admin_lain_tidak_bisa_mengakses_kegiatan_milik_orang_lain(): void
    {
        $pemilik = User::factory()->admin()->create();
        $penyusup = User::factory()->admin()->create();
        $kegiatan = Kegiatan::factory()->create(['created_by' => $pemilik->id]);
        $kehadiran = $this->kehadiranUntuk($kegiatan);

        $this->actingAs($penyusup);

        $this->get(route('admin.show', $kegiatan))->assertForbidden();
        $this->get(route('admin.edit', $kegiatan))->assertForbidden();
        $this->put(route('admin.update', $kegiatan), ['nama' => 'Dibajak'])->assertForbidden();
        $this->patch(route('admin.status', $kegiatan), ['status' => 'ditutup'])->assertForbidden();
        $this->get(route('admin.export.csv', $kegiatan))->assertForbidden();
        $this->get(route('admin.export.pdf', $kegiatan))->assertForbidden();
        $this->get(route('admin.tanda-tangan', [$kegiatan, $kehadiran]))->assertForbidden();
        $this->delete(route('admin.destroy', $kegiatan))->assertForbidden();

        $this->assertDatabaseHas('kegiatan', ['id' => $kegiatan->id, 'nama' => $kegiatan->nama, 'status' => 'aktif']);
    }

    public function test_pemilik_bisa_membuka_rekap_dan_dashboard_hanya_menampilkan_miliknya(): void
    {
        $admin = User::factory()->admin()->create();
        $milikku = Kegiatan::factory()->create(['created_by' => $admin->id, 'nama' => 'Rapat Milikku']);
        Kegiatan::factory()->create(['nama' => 'Rapat Orang Lain']);

        $this->actingAs($admin);

        $this->get(route('admin.show', $milikku))->assertOk()->assertSee('Rapat Milikku');
        $this->get(route('admin.index'))->assertOk()->assertSee('Rapat Milikku')->assertDontSee('Rapat Orang Lain');
    }

    public function test_admin_membuat_kegiatan_dan_field_sensitif_tidak_bisa_diisi_dari_input(): void
    {
        $admin = User::factory()->admin()->create();
        $lain = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.store'), [
            'nama' => 'Rapat Koordinasi 2026',
            'tanggal' => '12 Oktober 2026',
            // upaya mass assignment:
            'created_by' => $lain->id,
            'status' => 'ditutup',
            'slug' => 'slug-paksaan',
        ])->assertRedirect(route('admin.index'));

        $k = Kegiatan::where('nama', 'Rapat Koordinasi 2026')->firstOrFail();
        $this->assertSame($admin->id, (int) $k->created_by);
        $this->assertSame('aktif', $k->status);
        $this->assertNotSame('slug-paksaan', $k->slug);
        $this->assertStringStartsWith('rapat-koordinasi-2026-', $k->slug);
    }

    public function test_waktu_selesai_harus_setelah_waktu_mulai(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.store'), [
            'nama' => 'Rapat', 'mulai_at' => '2026-10-12T10:00', 'selesai_at' => '2026-10-12T09:00',
        ])->assertSessionHasErrors('selesai_at');
    }

    public function test_status_hanya_menerima_aktif_atau_ditutup(): void
    {
        $admin = User::factory()->admin()->create();
        $k = Kegiatan::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->patch(route('admin.status', $k), ['status' => 'apa-saja'])
            ->assertSessionHasErrors('status');
        $this->patch(route('admin.status', $k), ['status' => 'ditutup'])->assertRedirect();

        $this->assertSame('ditutup', $k->fresh()->status);
    }

    public function test_hapus_kegiatan_ikut_menghapus_data_dan_berkas_tanda_tangan(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $k = Kegiatan::factory()->create(['created_by' => $admin->id]);
        Storage::disk('local')->put('tanda-tangan/a.png', 'x');
        $this->kehadiranUntuk($k, ['tanda_tangan_path' => 'tanda-tangan/a.png']);

        $this->actingAs($admin)->delete(route('admin.destroy', $k))->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('kegiatan', ['id' => $k->id]);
        $this->assertDatabaseCount('kehadiran', 0);
        Storage::disk('local')->assertMissing('tanda-tangan/a.png');
    }

    public function test_tanda_tangan_hanya_bisa_diambil_lewat_kegiatan_pemiliknya(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $a = Kegiatan::factory()->create(['created_by' => $admin->id]);
        $b = Kegiatan::factory()->create(['created_by' => $admin->id]);
        Storage::disk('local')->put('tanda-tangan/a.png', 'isi-png');
        $kehadiranA = $this->kehadiranUntuk($a, ['tanda_tangan_path' => 'tanda-tangan/a.png']);

        $this->actingAs($admin);

        $this->get(route('admin.tanda-tangan', [$a, $kehadiranA]))->assertOk();
        // Baris milik kegiatan A tidak boleh bisa diambil lewat kegiatan B (walau B juga milik admin ini).
        $this->get(route('admin.tanda-tangan', [$b, $kehadiranA]))->assertNotFound();
    }

    public function test_ekspor_csv_menetralkan_formula_injection(): void
    {
        $admin = User::factory()->admin()->create();
        $k = Kegiatan::factory()->create(['created_by' => $admin->id]);
        $this->kehadiranUntuk($k, ['nama' => '=HYPERLINK("http://jahat.test")', 'jabatan' => '+cmd|calc']);

        $response = $this->actingAs($admin)->get(route('admin.export.csv', $k));

        $response->assertOk();
        $isi = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $isi);
        $this->assertStringContainsString("'+cmd|calc", $isi);
        $this->assertStringNotContainsString(',=HYPERLINK', $isi);
    }

    public function test_ekspor_pdf_menghasilkan_berkas_pdf(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $k = Kegiatan::factory()->create(['created_by' => $admin->id]);
        $this->kehadiranUntuk($k);

        $response = $this->actingAs($admin)->get(route('admin.export.pdf', $k));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pencarian_rekap_tidak_menafsirkan_wildcard_like(): void
    {
        $admin = User::factory()->admin()->create();
        $k = Kegiatan::factory()->create(['created_by' => $admin->id]);
        $this->kehadiranUntuk($k, ['nama' => 'Andi Wijaya']);

        // '%' murni dianggap karakter biasa, bukan "cocokkan semuanya".
        $this->actingAs($admin)->get(route('admin.show', [$k, 'q' => '%']))
            ->assertOk()->assertDontSee('Andi Wijaya');
    }
}
