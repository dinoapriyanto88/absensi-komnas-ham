
@extends('layouts.public')

@section('title', 'Absensi Digital Komnas HAM')

@section('content')
<div class="absen-info-page">
    <div class="absen-info-container">

        <section class="absen-info-card">

            <div class="absen-info-heading">
                <span class="absen-info-eyebrow">
                    KOMISI NASIONAL HAK ASASI MANUSIA
                </span>

                <h1>Absensi Digital</h1>

                <p class="absen-info-description">
                    Selamat datang di Sistem Absensi Digital Komnas HAM.
                    Silakan gunakan tautan presensi yang diberikan oleh
                    Admin atau penyelenggara kegiatan.
                </p>
            </div>

            <div class="absen-info-content">

                <div class="absen-info-icon" aria-hidden="true">
                    ✓
                </div>

                <h2>Presensi Kegiatan</h2>

                <p>
                    Setiap kegiatan memiliki tautan presensi tersendiri.
                    Untuk memastikan data kehadiran tercatat pada kegiatan
                    yang benar, buka tautan khusus yang Anda terima dari
                    penyelenggara.
                </p>

                <div class="absen-info-notice">
                    <span class="absen-info-notice-icon" aria-hidden="true">
                        i
                    </span>

                    <p>
                        Jika Anda belum menerima tautan presensi,
                        silakan hubungi Admin atau penyelenggara
                        kegiatan terkait.
                    </p>
                </div>

            </div>

        </section>

        <p class="absen-info-footer">
            Komisi Nasional Hak Asasi Manusia
        </p>

    </div>
</div>

<style>
/* =========================================
   HALAMAN INFORMASI ABSENSI DIGITAL
   ========================================= */

.absen-info-page {
    width: 100%;
    min-height: 65vh;
    padding: 42px 24px 56px;
    box-sizing: border-box;
}

.absen-info-container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}

.absen-info-card {
    width: 100%;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 12px 36px rgba(31, 41, 55, 0.07);
    box-sizing: border-box;
}

.absen-info-heading {
    padding: 36px 40px 30px;
    border-bottom: 1px solid #edf0f4;
}

.absen-info-eyebrow {
    display: inline-block;
    margin-bottom: 12px;
    color: #a51d32;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.2px;
    line-height: 1.6;
}

.absen-info-heading h1 {
    margin: 0 0 14px;
    color: #172033;
    font-size: clamp(28px, 4vw, 38px);
    font-weight: 800;
    line-height: 1.25;
}

.absen-info-description {
    max-width: 680px;
    margin: 0;
    color: #64748b;
    font-size: 15px;
    line-height: 1.8;
}

.absen-info-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 42px 40px 44px;
    text-align: center;
}

.absen-info-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 66px;
    height: 66px;
    margin-bottom: 20px;
    border-radius: 50%;
    background: #eaf8ef;
    color: #16804a;
    font-size: 36px;
    font-weight: 700;
}

.absen-info-content h2 {
    margin: 0 0 14px;
    color: #172033;
    font-size: 23px;
    font-weight: 750;
    line-height: 1.4;
}

.absen-info-content > p {
    max-width: 680px;
    margin: 0;
    color: #64748b;
    font-size: 15px;
    line-height: 1.9;
}

.absen-info-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    width: 100%;
    max-width: 650px;
    margin-top: 26px;
    padding: 16px 18px;
    border: 1px solid #f1d9de;
    border-radius: 12px;
    background: #fff8f9;
    box-sizing: border-box;
    text-align: left;
}

.absen-info-notice-icon {
    display: flex;
    flex: 0 0 22px;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #a51d32;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
}

.absen-info-notice p {
    margin: 0;
    color: #6b3540;
    font-size: 14px;
    line-height: 1.8;
}

.absen-info-footer {
    margin: 22px 0 0;
    color: #8490a2;
    font-size: 12px;
    line-height: 1.7;
    text-align: center;
}

/* Responsif */
@media (max-width: 600px) {
    .absen-info-page {
        min-height: auto;
        padding: 24px 14px 36px;
    }

    .absen-info-heading {
        padding: 26px 22px 24px;
    }

    .absen-info-heading h1 {
        font-size: 28px;
    }

    .absen-info-description {
        font-size: 14px;
    }

    .absen-info-content {
        padding: 30px 20px;
    }

    .absen-info-content h2 {
        font-size: 21px;
    }

    .absen-info-content > p {
        font-size: 14px;
    }

    .absen-info-notice {
        padding: 14px;
    }
}
</style>
@endsection