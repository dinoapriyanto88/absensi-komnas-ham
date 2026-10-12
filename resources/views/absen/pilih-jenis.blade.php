@extends('layouts.public')
@section('title', 'Pilih Jenis Peserta')

@section('content')

<div class="participant-page">

    <div class="participant-container">

        {{-- HEADER --}}
        <div class="participant-heading">

            <div class="participant-label">
                <span></span>
                ABSENSI DIGITAL
            </div>

            <h2>
                {{ $kegiatan->nama }}
            </h2>

            <div class="participant-meta">
                @if ($kegiatan->mulai_at || $kegiatan->selesai_at)
                    <span>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="17" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 9h18"/>
                        </svg>
                        {{ $kegiatan->jadwal }}
                    </span>
                @endif

                <span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                    Kehadiran Peserta
                </span>
            </div>

            @if ($kegiatan->deskripsi)
                <p style="color:var(--ink);font-weight:500;white-space:pre-line;margin-bottom:6px;">{{ $kegiatan->deskripsi }}</p>
            @endif

            <p>
                Silakan pilih kategori peserta yang sesuai untuk melanjutkan
                proses absensi kegiatan.
            </p>

        </div>


        {{-- PARTICIPANT OPTIONS --}}
        <div class="participant-grid">

            {{-- INTERNAL --}}
            <a
                href="{{ route('absen.form', [$kegiatan, 'internal']) }}"
                class="participant-card"
            >

                <div class="participant-card-top">

                    <div class="participant-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>

                    <span class="participant-number">01</span>

                </div>

                <div class="participant-card-content">
                    <span class="participant-type">
                        KOMNAS HAM
                    </span>

                    <h3>Internal</h3>

                    <p>
                        Pegawai, staf, atau anggota internal
                        Komisi Nasional Hak Asasi Manusia.
                    </p>
                </div>

                <div class="participant-card-footer">
                    <span>Pilih kategori</span>

                    <span class="participant-arrow">
                        →
                    </span>
                </div>

            </a>


            {{-- EKSTERNAL --}}
            <a
                href="{{ route('absen.form', [$kegiatan, 'eksternal']) }}"
                class="participant-card"
            >

                <div class="participant-card-top">

                    <div class="participant-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 21h18"/>
                            <path d="M5 21V7l7-4 7 4v14"/>
                            <path d="M9 21v-4h6v4"/>
                            <path d="M9 10h1M14 10h1M9 13h1M14 13h1"/>
                        </svg>
                    </div>

                    <span class="participant-number">02</span>

                </div>

                <div class="participant-card-content">
                    <span class="participant-type">
                        MITRA
                    </span>

                    <h3>Eksternal</h3>

                    <p>
                        Mitra, instansi pemerintah, organisasi,
                        narasumber, atau pihak dari luar Komnas HAM.
                    </p>
                </div>

                <div class="participant-card-footer">
                    <span>Pilih kategori</span>

                    <span class="participant-arrow">
                        →
                    </span>
                </div>

            </a>


            {{-- TAMU --}}
            <a
                href="{{ route('absen.form', [$kegiatan, 'tamu']) }}"
                class="participant-card"
            >

                <div class="participant-card-top">

                    <div class="participant-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                    </div>

                    <span class="participant-number">03</span>

                </div>

                <div class="participant-card-content">
                    <span class="participant-type">
                        UMUM
                    </span>

                    <h3>Tamu</h3>

                    <p>
                        Peserta umum, masyarakat, atau tamu
                        yang menghadiri kegiatan.
                    </p>
                </div>

                <div class="participant-card-footer">
                    <span>Pilih kategori</span>

                    <span class="participant-arrow">
                        →
                    </span>
                </div>

            </a>

        </div>


        {{-- BACK --}}
        <div class="participant-back">
            <a href="{{ route('absen.index') }}">
                <span>←</span>
                Kembali ke daftar kegiatan
            </a>
        </div>

    </div>

</div>

@endsection