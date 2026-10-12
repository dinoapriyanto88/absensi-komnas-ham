<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_berhasil_dan_diarahkan_ke_dashboard(): void
    {
        User::factory()->admin()->create(['username' => 'adminku']);

        $this->post('/login', ['username' => 'AdminKu', 'password' => 'Password123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_password_salah_memberi_pesan_generik(): void
    {
        User::factory()->admin()->create(['username' => 'adminku']);

        $this->post('/login', ['username' => 'adminku', 'password' => 'salah'])
            ->assertSessionHasErrors(['username' => 'Username atau password salah.']);
        $this->post('/login', ['username' => 'tidak.ada', 'password' => 'salah'])
            ->assertSessionHasErrors(['username' => 'Username atau password salah.']);

        $this->assertGuest();
    }

    public function test_login_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        User::factory()->admin()->create(['username' => 'adminku']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'adminku', 'password' => 'salah']);
        }
        $this->post('/login', ['username' => 'adminku', 'password' => 'Password123']);

        $this->assertGuest();
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('username'));
    }

    public function test_tidak_ada_pendaftaran_akun_publik(): void
    {
        $this->get('/daftar')->assertNotFound();
        $this->post('/daftar/tamu', ['username' => 'x', 'password' => 'Password123'])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
