@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('subtitle', 'Masuk sebagai '.auth()->user()->nama.' (Admin)')

@section('content')
<div class="admin-dashboard">

  @include('partials.flash')

  {{-- HEADER DASHBOARD --}}
  <div class="dashboard-head">
    <div>
      <span class="dashboard-eyebrow">DASHBOARD ADMIN</span>
      <h1>Selamat datang, {{ auth()->user()->nama }}</h1>
      <p>Kelola kegiatan, absensi peserta, dan rekap kehadiran melalui satu dashboard.</p>
    </div>

    <div class="dashboard-head-icon">
      <span>HAM</span>
    </div>
  </div>

  {{-- STATISTIK --}}
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon red"><span>01</span></div>
      <div class="stat-content">
        <span class="stat-label">KEGIATAN SAYA</span>
        <strong>{{ $kegiatan->count() }}</strong>
        <small>Total kegiatan yang Anda kelola</small>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon green"><span>02</span></div>
      <div class="stat-content">
        <span class="stat-label">KEGIATAN AKTIF</span>
        <strong>{{ $kegiatan->where('status', 'aktif')->count() }}</strong>
        <small>Absensi yang sedang dibuka</small>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon blue"><span>03</span></div>
      <div class="stat-content">
        <span class="stat-label">TOTAL KEHADIRAN</span>
        <strong>{{ $kegiatan->sum('kehadiran_count') }}</strong>
        <small>Total peserta yang tercatat</small>
      </div>
    </div>
  </div>

  {{-- 1. TAMBAH KEGIATAN (kotak tersendiri; formulirnya ada di popup di bawah) --}}
  <section class="kg-box kg-add">
    <div class="kg-add-text">
      <span class="section-label">TAMBAH KEGIATAN</span>
      <h2>Buat kegiatan baru</h2>
      <p>Setiap kegiatan punya tautan absensinya sendiri yang bisa dibagikan ke peserta.</p>
    </div>

    <button type="button" class="btn kg-add-btn" data-kg-open="kgCreateModal">
      + Buat Kegiatan
    </button>
  </section>

  {{-- 2. CARI & FILTER KEGIATAN --}}
  @unless ($kegiatan->isEmpty())
    <section class="kg-box kg-search">
      <span class="section-label">CARI KEGIATAN</span>
      <h2>Temukan kegiatan dengan cepat</h2>

      <div class="kg-filter-row">
        <label class="kg-search-field" for="kgCari">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m20 20-3.5-3.5"></path>
          </svg>
          <input type="search" id="kgCari" autocomplete="off"
                 placeholder="Cari nama, tanggal, atau jadwal kegiatan...">
        </label>

        <select id="kgStatus" aria-label="Filter status">
          <option value="">Semua status</option>
          <option value="aktif">Aktif</option>
          <option value="ditutup">Ditutup</option>
        </select>

        <button type="button" class="btn secondary" id="kgReset" hidden>Reset</button>
      </div>

      <p class="kg-count" id="kgHitung" role="status" aria-live="polite"></p>
    </section>
  @endunless

  {{-- 3. DAFTAR KEGIATAN (satu kartu per kegiatan) --}}
  <section class="kg-box kg-list">
    <div class="kg-list-head">
      <span class="section-label">KEGIATAN SAYA</span>
      <h2>Kegiatan yang Anda Kelola</h2>
      <p>Kelola status, rekap peserta, dan informasi kegiatan yang telah Anda buat.</p>
    </div>

    @if ($kegiatan->isEmpty())
      <div class="kg-empty">
        <h3>Belum ada kegiatan</h3>
        <p>Anda belum membuat kegiatan. Buat kegiatan pertama Anda sekarang.</p>
        <button type="button" class="btn" data-kg-open="kgCreateModal">+ Buat Kegiatan</button>
      </div>
    @else
      <div class="kg-grid" id="kgDaftar">
        @foreach ($kegiatan as $k)
          @php
            $fmt = fn ($t) => $t->translatedFormat('j M Y, H:i');
            $jadwal = match (true) {
                $k->mulai_at && $k->selesai_at => $fmt($k->mulai_at).' — '.$fmt($k->selesai_at),
                (bool) $k->mulai_at => 'Mulai '.$fmt($k->mulai_at),
                (bool) $k->selesai_at => 'Sampai '.$fmt($k->selesai_at),
                default => 'Tanpa batas waktu',
            };
            $aktif = $k->status === 'aktif';
          @endphp

          <article class="kg-item"
                   data-kg-item
                   data-status="{{ $k->status }}"
                   data-cari="{{ mb_strtolower($k->nama.' '.$k->tanggal.' '.$k->deskripsi.' '.$jadwal) }}">

            <div class="kg-item-head">
              <h3>{{ $k->nama }}</h3>

              <form class="kg-status-form" method="POST" action="{{ route('admin.status', $k) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $aktif ? 'ditutup' : 'aktif' }}">
                <button type="submit"
                        class="kg-status {{ $aktif ? 'is-aktif' : 'is-ditutup' }}"
                        title="Klik untuk {{ $aktif ? 'menutup' : 'membuka' }} absensi">
                  {{ $aktif ? 'Aktif' : 'Ditutup' }}
                </button>
              </form>
            </div>

            @if ($k->deskripsi)
              <p class="kg-desc">{{ $k->deskripsi }}</p>
            @endif

            <dl class="kg-info">
              <div><dt>Tanggal</dt><dd>{{ $k->tanggal ?: '-' }}</dd></div>
              <div><dt>Peserta</dt><dd>{{ $k->kehadiran_count }} orang</dd></div>
              <div><dt>Jadwal</dt><dd>{{ $jadwal }}</dd></div>
            </dl>

            <div class="kg-actions">
              <a class="btn small" href="{{ route('admin.show', $k) }}">Lihat Rekap</a>
              <a class="btn small secondary" href="{{ route('admin.edit', $k) }}">Edit</a>

              <button type="button"
                      class="btn small secondary"
                      data-kg-copy="{{ route('absen.jenis', $k) }}"
                      data-kg-title="{{ $k->nama }}">Share Link</button>

              <form method="POST"
                    action="{{ route('admin.destroy', $k) }}"
                    data-confirm-action="delete"
                    data-confirm-title="Hapus Kegiatan?"
                    data-confirm="Hapus kegiatan ini beserta seluruh datanya? Tindakan ini tidak bisa dibatalkan.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn small danger">Hapus</button>
              </form>
            </div>
          </article>
        @endforeach
      </div>

      <div class="kg-empty" id="kgKosong" hidden>
        <h3>Tidak ada hasil</h3>
        <p>Tidak ada kegiatan yang cocok dengan pencarian atau filter Anda.</p>
      </div>
    @endif
  </section>

  {{-- INFORMASI BAWAH --}}
  <div class="dashboard-footer-note">
    <div class="note-symbol">i</div>
    <p>
      Pastikan status kegiatan sudah <strong>Aktif</strong>
      sebelum membagikan tautan absensi kepada peserta.
    </p>
  </div>

  {{-- POPUP BUAT KEGIATAN (dipindahkan ke <body> oleh dashboard-kegiatan.js) --}}
  <div class="kg-modal" id="kgCreateModal" hidden
       data-auto-open="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="kg-modal-backdrop" data-kg-close></div>

    <section class="kg-modal-dialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="kgCreateTitle">

      <header class="kg-modal-head">
        <div class="kg-modal-icon" aria-hidden="true">+</div>

        <div class="kg-modal-titles">
          <h2 id="kgCreateTitle">Buat Kegiatan Baru</h2>
          <p>Isi data kegiatan. Setelah disimpan, tautan absensinya langsung bisa dibagikan.</p>
        </div>

        <button type="button" class="kg-modal-x" data-kg-close aria-label="Tutup">&times;</button>
      </header>

      <form class="kg-modal-form"
            method="POST"
            action="{{ route('admin.store') }}"
            data-confirm-action="create"
            data-confirm-title="Simpan Kegiatan Baru?"
            data-confirm="Apakah Anda yakin ingin menyimpan kegiatan baru ini?">
        @csrf

        <div class="kg-modal-body">
          @include('admin._form-kegiatan', ['kegiatan' => null])
        </div>

        <footer class="kg-modal-foot">
          <button type="button" class="btn secondary" data-kg-close>Batal</button>
          <button type="submit" class="btn">Simpan Kegiatan</button>
        </footer>
      </form>
    </section>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard-kegiatan.js') }}" defer></script>
@endpush