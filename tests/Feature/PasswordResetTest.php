<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_reset_dikirim_untuk_akun_yang_punya_email(): void
    {
        Notification::fake();
        $user = User::factory()->admin()->create(['username' => 'budi', 'email' => 'budi@example.test']);

        $this->post('/forgot-password', ['username' => 'budi'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_balasan_sama_untuk_username_yang_tidak_ada_atau_tanpa_email(): void
    {
        Notification::fake();
        User::factory()->admin()->create(['username' => 'tanpa.email', 'email' => null]);
        $pesan = 'Jika akun tersebut ada dan memiliki email terdaftar, tautan reset sudah dikirim. Periksa kotak masuk atau folder spam.';

        $this->post('/forgot-password', ['username' => 'tanpa.email'])->assertSessionHas('status', $pesan);
        $this->post('/forgot-password', ['username' => 'tidak.ada.sama.sekali'])->assertSessionHas('status', $pesan);

        Notification::assertNothingSent();
    }

    public function test_tautan_reset_tidak_pernah_ditampilkan_di_layar(): void
    {
        Notification::fake();
        User::factory()->admin()->create(['username' => 'budi', 'email' => 'budi@example.test']);

        $this->followingRedirects()
            ->post('/forgot-password', ['username' => 'budi'])
            ->assertDontSee('reset-password/');
    }

    public function test_password_bisa_direset_dengan_token_valid(): void
    {
        $user = User::factory()->admin()->create(['email' => 'budi@example.test']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'budi@example.test',
            'password' => 'PasswordBaru456', 'password_confirmation' => 'PasswordBaru456',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('PasswordBaru456', $user->fresh()->password));

        // Token hanya sekali pakai.
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'budi@example.test',
            'password' => 'PasswordLain789', 'password_confirmation' => 'PasswordLain789',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_ditolak_untuk_token_salah_dan_password_lemah(): void
    {
        $user = User::factory()->admin()->create(['email' => 'budi@example.test']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => 'token-ngawur', 'email' => 'budi@example.test',
            'password' => 'PasswordBaru456', 'password_confirmation' => 'PasswordBaru456',
        ])->assertSessionHasErrors('email');

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'budi@example.test',
            'password' => 'lemah', 'password_confirmation' => 'lemah',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }
}
