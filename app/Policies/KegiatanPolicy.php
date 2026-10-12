<?php

namespace App\Policies;

use App\Models\Kegiatan;
use App\Models\User;

class KegiatanPolicy
{
    /** Admin hanya boleh mengelola kegiatan yang ia buat sendiri. */
    public function manage(User $user, Kegiatan $kegiatan): bool
    {
        return $user->isAdmin() && (int) $kegiatan->created_by === (int) $user->getKey();
    }
}
