<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $menit = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Reset Password — Absensi Digital Komnas HAM')
            ->greeting('Halo '.$notifiable->nama.',')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda di Sistem Absensi Digital Komnas HAM.')
            ->action('Atur Ulang Password', $this->resetUrl($notifiable))
            ->line("Tautan ini berlaku selama {$menit} menit dan hanya dapat dipakai sekali.")
            ->line('Jika Anda tidak meminta ini, abaikan email ini. Password Anda tidak akan berubah.')
            ->salutation('Salam, Absensi Digital Komnas HAM');
    }
}
