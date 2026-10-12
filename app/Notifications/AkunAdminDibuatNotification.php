<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Pemberitahuan akun Admin baru. Password TIDAK PERNAH dikirim lewat email. */
class AkunAdminDibuatNotification extends Notification
{
    public function __construct(private ?string $dibuatOleh = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Admin Anda Telah Dibuat - Absensi Digital Komnas HAM')
            ->greeting('Halo '.MarkdownAman::escape($notifiable->nama).',')
            ->line('Akun Admin Anda di Sistem Absensi Digital Komnas HAM telah berhasil dibuat'
                .($this->dibuatOleh ? ' oleh '.MarkdownAman::escape($this->dibuatOleh) : ' oleh Superadmin').'.')
            ->line('Username: `'.$notifiable->username.'`')
            ->line('Dibuat pada: '.now()->locale('id')->translatedFormat('l, j F Y, H:i'))
            ->action('Buka Halaman Login', route('login'))
            ->line('Password awal disampaikan Superadmin secara terpisah dan tidak dikirim lewat email. Anda akan diminta menggantinya saat login pertama.')
            ->line('Jika Anda tidak merasa akan menerima akun ini, abaikan email ini.')
            ->salutation('Absensi Digital Komnas HAM');
    }
}