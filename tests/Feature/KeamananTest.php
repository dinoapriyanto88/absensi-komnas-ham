<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeamananTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_keamanan_terpasang_dan_csp_tanpa_unsafe_inline(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'same-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_halaman_berisi_data_pribadi_tidak_boleh_di_cache(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_tidak_ada_skrip_atau_gaya_inline_agar_cocok_dengan_csp(): void
    {
        $admin = User::factory()->admin()->create();
        $k = Kegiatan::factory()->create(['created_by' => $admin->id]);

        $halaman = [
            [null, '/login'], [null, '/forgot-password'],
            [null, '/absen'], [null, route('absen.jenis', $k)],
            [null, route('absen.form', [$k, 'internal'])], [null, route('absen.form', [$k, 'eksternal'])], [null, route('absen.form', [$k, 'tamu'])],
            [$admin, '/admin'], [$admin, route('admin.show', $k)], [$admin, route('admin.edit', $k)],
            [User::factory()->superadmin()->create(), '/superadmin'],
        ];

        foreach ($halaman as [$user, $url]) {
            $this->flushSession();
            $response = $user ? $this->actingAs($user)->get($url) : $this->get($url);
            $response->assertOk();

            $html = $response->getContent();
            $this->assertStringNotContainsString(' style="', $html, "style inline di {$url}");
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\ssrc=)/i', $html, "skrip inline di {$url}");
            $this->assertDoesNotMatchRegularExpression('/\son(click|submit|load|change)=/i', $html, "event handler inline di {$url}");
        }
    }

    public function test_keluaran_di_halaman_di_escape_terhadap_xss(): void
    {
        $admin = User::factory()->admin()->create();
        Kegiatan::factory()->create(['created_by' => $admin->id, 'nama' => '<script>alert(1)</script>']);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_halaman_publik_tidak_memuat_sumber_daya_pihak_ketiga(): void
    {
        $k = Kegiatan::factory()->create();

        $html = $this->get(route('absen.form', [$k, 'tamu']))->getContent();

        // Semua src/href harus relatif atau satu domain: tidak ada CDN, reCAPTCHA, atau Google Fonts.
        preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/i', $html, $m);
        foreach ($m[1] as $url) {
            $this->assertStringStartsWith(config('app.url'), $url, "sumber luar terdeteksi: {$url}");
        }
    }

    public function test_zona_waktu_aplikasi_asia_jakarta(): void
    {
        // Jendela waktu kegiatan dihitung menurut zona waktu aplikasi.
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_password_tidak_pernah_ikut_terserialisasi(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}
