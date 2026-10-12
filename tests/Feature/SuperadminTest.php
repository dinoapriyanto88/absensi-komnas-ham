<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\User;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_membuat_admin_dan_admin_wajib_ganti_password(): void
    {
        $this->actingAs(User::factory()->superadmin()->create());

        $this->post(route('superadmin.store'), [
            'nama' => 'Admin Baru', 'username' => 'admin.baru', 'password' => 'lemah',
        ])->assertSessionHasErrors('password');

        $this->post(route('superadmin.store'), [
            'nama' => 'Admin Baru', 'username' => 'Admin.Baru', 'password' => 'PasswordAwal123',
        ])->assertRedirect(route('superadmin.index'));

        $admin = User::where('username', 'admin.baru')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->must_change_password);
    }

    public function test_reset_password_admin_memberi_password_sementara_lewat_flash_bukan_url(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'admin.lama']);
        $this->actingAs(User::factory()->superadmin()->create());

        $response = $this->post(route('superadmin.reset', $admin));

        $response->assertRedirect(route('superadmin.index'));
        $this->assertStringNotContainsString('password', (string) $response->headers->get('Location'));
        $response->assertSessionHas('password_sementara');

        $admin->refresh();
        $this->assertTrue($admin->must_change_password);
        $this->assertTrue(Hash::check(session('password_sementara'), $admin->password));
        $this->assertFalse(Hash::check('Password123', $admin->password));
    }

    public function test_superadmin_hanya_bisa_mereset_dan_menghapus_akun_admin(): void
    {
        $lain = User::factory()->superadmin()->create();
        $this->actingAs(User::factory()->superadmin()->create());

        // Superadmin lain tidak boleh direset/dihapus lewat rute pengelolaan admin.
        $this->post(route('superadmin.reset', $lain))->assertNotFound();
        $this->delete(route('superadmin.destroy', $lain))->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $lain->id]);
    }

    public function test_admin_yang_masih_punya_kegiatan_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        Kegiatan::factory()->create(['created_by' => $admin->id]);
        $this->actingAs(User::factory()->superadmin()->create());

        $this->delete(route('superadmin.destroy', $admin))->assertSessionHasErrors('hapus');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_tanpa_kegiatan_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->superadmin()->create());

        $this->delete(route('superadmin.destroy', $admin))->assertRedirect(route('superadmin.index'));

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_seeder_menolak_password_superadmin_yang_lemah_atau_kosong(): void
    {
        foreach ([null, '', 'admin123', 'pendek12345'] as $lemah) {
            config(['absensi.superadmin.password' => $lemah]);
            $this->seed(SuperadminSeeder::class);
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_membuat_superadmin_dengan_password_kuat_dan_wajib_ganti(): void
    {
        config(['absensi.superadmin.password' => 'PasswordKuatSekali123']);

        $this->seed(SuperadminSeeder::class);
        $this->seed(SuperadminSeeder::class); // idempoten

        $this->assertSame(1, User::where('role', 'superadmin')->count());
        $this->assertTrue(User::where('role', 'superadmin')->first()->must_change_password);
    }
}
