<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_absensi_publik_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/')->assertRedirect('/absen');
        $this->get('/absen')->assertOk();
    }

    public function test_halaman_admin_menolak_pengunjung_yang_belum_login(): void
    {
        foreach (['/dashboard', '/admin', '/superadmin', '/akun/password'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_tidak_bisa_membuka_halaman_superadmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/superadmin')->assertForbidden();
        $this->post('/superadmin/admin', ['username' => 'x'])->assertForbidden();
    }

    public function test_superadmin_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->superadmin()->create());

        $this->get('/admin')->assertForbidden();
        $this->post('/admin/kegiatan', ['nama' => 'X'])->assertForbidden();
    }

    public function test_dashboard_mengarahkan_sesuai_role(): void
    {
        $this->actingAs(User::factory()->superadmin()->create())->get('/dashboard')->assertRedirect(route('superadmin.index'));
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertRedirect(route('admin.index'));
    }

    public function test_akun_dengan_password_sementara_dipaksa_ganti_password(): void
    {
        $admin = User::factory()->admin()->create(['must_change_password' => true]);
        $this->actingAs($admin);

        $this->get('/admin')->assertRedirect(route('akun.password.edit'));
        $this->get('/akun/password')->assertOk();
    }

    public function test_ganti_password_membutuhkan_password_lama_dan_melepas_paksaan(): void
    {
        $admin = User::factory()->admin()->create(['must_change_password' => true]);
        $this->actingAs($admin);

        $this->put('/akun/password', [
            'current_password' => 'SALAH', 'password' => 'PasswordBaru456', 'password_confirmation' => 'PasswordBaru456',
        ])->assertSessionHasErrors('current_password');

        $this->put('/akun/password', [
            'current_password' => 'Password123', 'password' => 'PasswordBaru456', 'password_confirmation' => 'PasswordBaru456',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($admin->fresh()->must_change_password);
    }
}
