<?php

namespace App\Notifications;

use App\Models\Kegiatan;
use App\Models\Kehadiran;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Salinan bukti kehadiran untuk peserta (dikirim ke alamat yang ia ketik sendiri di form). */
class SalinanAbsensiNotification extends Notification
{
    public function __construct(private Kegiatan $kegiatan, private Kehadiran $kehadiran) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $k = $this->kehadiran;

        $pesan = (new MailMessage)
            ->subject('Salinan Absensi — '.$this->kegiatan->nama)
            ->greeting('Terima kasih, kehadiran Anda tercatat.')
            ->line('Kegiatan: '.$this->kegiatan->nama)
            ->line('Nama: '.$k->nama)
            ->line('Jenis peserta: '.ucfirst($k->jenis_peserta).($k->tipe_peserta ? ' ('.$k->tipe_peserta.')' : ''));

        foreach ([
            'Jabatan' => $k->jabatan,
            'Instansi' => $k->instansi,
            'Unit kerja' => $k->unit_kerja,
        ] as $label => $nilai) {
            if ($nilai) {
                $pesan->line($label.': '.$nilai);
            }
        }

        return $pesan
            ->line('Waktu: '.$k->created_at->translatedFormat('j F Y, H:i').' WIB')
            ->line('Email ini dikirim karena seseorang meminta salinan absensi ke alamat ini. Jika bukan Anda, abaikan saja.')
            ->salutation('Absensi Digital Komnas HAM');
    }
}
