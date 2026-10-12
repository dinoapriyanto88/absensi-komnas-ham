@extends('layouts.public')
@section('title', 'Absensi Kegiatan')

@section('content')

<div class="absen-page">

  {{-- HERO --}}
  <section class="absen-hero">
    <div class="absen-hero-content">

      <div class="absen-eyebrow">
        <span class="eyebrow-dot"></span>
        SISTEM KEHADIRAN DIGITAL
      </div>

      <h1>
        Pilih Kegiatan<br>
        yang Anda Hadiri
      </h1>

      <p>
        Silakan pilih kegiatan yang sedang berlangsung untuk melakukan
        absensi. Anda tidak perlu membuat akun atau melakukan login.
      </p>

    </div>

    <div class="hero-decoration">
      <div class="hero-circle circle-1"></div>
      <div class="hero-circle circle-2"></div>
      <div class="hero-circle circle-3"></div>

      <div class="hero-badge">
        <span>✓</span>
        <div>
          <strong>Absensi Mudah</strong>
          <small>Tanpa perlu login</small>
        </div>
      </div>
    </div>
  </section>


  {{-- DAFTAR KEGIATAN --}}
  <section class="activity-section">

    <div class="section-heading">
      <div>
        <span class="section-label">KEGIATAN TERSEDIA</span>
        <h2>Daftar Kegiatan</h2>
      </div>

      <div class="activity-info">
        <span class="activity-info-icon">●</span>
        Absensi sedang dibuka
      </div>
    </div>


    @forelse ($daftar as $k)

      <article class="activity-card">

        <div class="activity-card-left">

          <div class="activity-icon">
            <span>✓</span>
          </div>

          <div class="activity-content">

            <div class="activity-status">
              <span class="status-dot"></span>
              ABSENSI DIBUKA
            </div>

            <h3>{{ $k->nama }}</h3>

            @if ($k->tanggal)
              <div class="activity-date">
                <span class="date-icon">▣</span>
                {{ $k->tanggal }}
              </div>
            @endif

            @if ($k->deskripsi)
              <p>{{ $k->deskripsi }}</p>
            @endif

          </div>

        </div>


        <div class="activity-card-right">

          <a
            class="attendance-button"
            href="{{ route('absen.jenis', $k) }}"
          >
            <span>Isi Absensi</span>
            <span class="button-arrow">→</span>
          </a>

        </div>

      </article>

    @empty

      <div class="empty-activity">

        <div class="empty-icon">
          <span>—</span>
        </div>

        <h3>Belum Ada Kegiatan</h3>

        <p>
          Saat ini belum ada kegiatan yang membuka absensi.
          Silakan kembali lagi nanti.
        </p>

      </div>

    @endforelse

  </section>
</div>

@endsection