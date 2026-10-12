<?php

namespace Database\Factories;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Kegiatan> */
class KegiatanFactory extends Factory
{
    protected $model = Kegiatan::class;

    public function definition(): array
    {
        $nama = 'Rapat '.fake()->unique()->words(2, true);

        return [
            'nama' => $nama,
            'slug' => Str::slug($nama).'-'.Str::lower(Str::random(5)),
            'deskripsi' => fake()->sentence(),
            'tanggal' => '12 Oktober 2026',
            'status' => 'aktif',
            'created_by' => User::factory()->admin(),
        ];
    }
}
