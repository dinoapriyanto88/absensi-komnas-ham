<?php

namespace App\Notifications;

/**
 * Email Laravel menafsirkan Markdown. Teks isian pengguna wajib di-escape, supaya
 * seseorang tidak bisa menyelipkan tautan atau format ke email yang dikirim sistem.
 */
class MarkdownAman
{
    public static function escape(?string $teks): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|<>~])/', '\\\\$1', (string) $teks);
    }
}