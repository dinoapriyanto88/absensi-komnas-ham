<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('slug', 190)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('tanggal', 60)->nullable();
            $table->dateTime('mulai_at')->nullable();
            $table->dateTime('selesai_at')->nullable();
            $table->string('status', 10)->default('aktif')->index();
            // restrict: admin yang masih punya kegiatan tidak bisa terhapus diam-diam beserta datanya
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
