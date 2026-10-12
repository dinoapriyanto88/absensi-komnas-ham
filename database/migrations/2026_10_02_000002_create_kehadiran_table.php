<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kehadiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->string('jenis_peserta', 20);
            $table->string('nama', 150);
            $table->string('tipe_peserta', 20)->nullable();
            $table->string('jabatan', 150)->nullable();
            $table->string('instansi', 150)->nullable();
            $table->string('unit_kerja', 150)->nullable();
            $table->text('nip_nik')->nullable();   // terenkripsi di level aplikasi
            $table->text('no_telp')->nullable();   // terenkripsi di level aplikasi
            // Sidik jari (HMAC) dari NIP / nomor telepon. Dipakai mencegah absen ganda
            // tanpa menyimpan nomornya dalam bentuk terbaca di kolom yang diindeks.
            $table->string('kunci_unik', 64);
            // Tanda tangan disimpan sebagai berkas PNG di disk privat, bukan di dalam tabel.
            $table->string('tanda_tangan_path');
            $table->timestamps();

            $table->unique(['kegiatan_id', 'kunci_unik']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kehadiran');
    }
};
