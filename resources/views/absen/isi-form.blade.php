
@extends('layouts.public')
@section('title', 'Isi Absensi')

@section('content')
<div class="absen-page">
  <div class="wrap wrap-sm">

    {{-- HEADER KEGIATAN --}}
    <div class="attendance-header">
      <div class="attendance-header-main">
        <div class="attendance-icon">✓</div>
        <div>
          <span class="attendance-label">FORMULIR KEHADIRAN</span>
          <h1>{{ $kegiatan->nama }}</h1>
          <div class="attendance-meta">
            @if ($kegiatan->tanggal)
              <span><span class="meta-icon">▣</span> {{ $kegiatan->tanggal }}</span>
            @endif
            <span><span class="meta-icon">●</span> {{ ucfirst($jenis) }}</span>
          </div>
        </div>
      </div>
      <a class="change-type" href="{{ route('absen.jenis', $kegiatan) }}">Ganti jenis</a>
    </div>

    <div class="attendance-card">
      <div class="form-intro">
        <div>
          <span class="section-number">01</span>
          <div>
            <h2>Data Kehadiran</h2>
            <p>Lengkapi informasi berikut sesuai dengan data Anda.</p>
          </div>
        </div>
      </div>

      @if ($errors->any())
        <div class="alert error">
          <strong>Data belum dapat dikirim.</strong>
          <span>Mohon periksa kembali isian yang ditandai.</span>
        </div>
      @endif

      @php
        $pakaiLookup = collect($fields)->contains(fn ($f) => !empty($f['lookup']));
        $fieldLookup = collect($fields)->first(fn ($f) => !empty($f['lookup']));
        $lookupKey = $fieldLookup ? array_search($fieldLookup, $fields, true) : null;
        $fieldNama = $fields['nama'] ?? null;
        $fieldUnit = $fields['unit_kerja'] ?? null;
        $fieldJabatan = $fields['jabatan'] ?? null;
      @endphp

      <form
        id="absenForm"
        method="POST"
        action="{{ route('absen.submit', [$kegiatan, $jenis]) }}"
        novalidate
        @if ($pakaiLookup)
          data-cari-url="{{ route('absen.cari-nip', $kegiatan) }}"
        @endif
      >
        @csrf
        <input type="hidden" name="_t" value="{{ $token }}">

        {{-- HONEYPOT --}}
        <div class="hp" aria-hidden="true">
          <label for="website">Jangan diisi</label>
          <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        {{-- DATA PESERTA --}}
        <div class="form-section">
          <div class="fields-grid">
            @if ($pakaiLookup)
              <div class="field full {{ $errors->has($lookupKey) ? 'has-error' : '' }}">
                <label for="f-{{ $lookupKey }}">
                  {{ $fieldLookup['label'] }}
                  @if ($fieldLookup['required']) <span class="req">*</span> @endif
                </label>

                <div class="nip-row">
                  <input
                    id="f-{{ $lookupKey }}"
                    type="text"
                    name="{{ $lookupKey }}"
                    value="{{ old($lookupKey) }}"
                    maxlength="20"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Ketik NIP Anda"
                    required
                  >
                  <button type="button" class="btn small" id="cariNip">Cari Data</button>
                </div>

                <p class="hint" id="nipStatus" role="status" aria-live="polite">
                  Masukkan NIP, lalu klik Cari Data untuk menampilkan profil pegawai.
                </p>
                <p class="error-text">{{ $errors->first($lookupKey) }}</p>
              </div>

              <div class="field full simpeg-result" id="simpegResult" hidden>
                <div class="simpeg-result-item">
                  <span class="simpeg-result-label">{{ $fieldNama['label'] ?? 'Nama Lengkap' }}</span>
                  <strong id="hasilNama">—</strong>
                  <input type="hidden" id="f-nama" name="nama" value="{{ old('nama') }}">
                </div>
                <div class="simpeg-result-item">
                  <span class="simpeg-result-label">{{ $fieldUnit['label'] ?? 'Unit Kerja' }}</span>
                  <strong id="hasilUnitKerja">—</strong>
                  <input type="hidden" id="f-unit_kerja" name="unit_kerja" value="{{ old('unit_kerja') }}">
                </div>
                <div class="simpeg-result-item">
                  <span class="simpeg-result-label">{{ $fieldJabatan['label'] ?? 'Jabatan' }}</span>
                  <strong id="hasilJabatan">—</strong>
                  <input type="hidden" id="f-jabatan" name="jabatan" value="{{ old('jabatan') }}">
                </div>
              </div>

              @foreach ($fields as $key => $f)
                @if ($key !== $lookupKey && !in_array($key, ['nama', 'unit_kerja', 'jabatan'], true))
                  <div class="field {{ $errors->has($key) ? 'has-error' : '' }}">
                    <label for="f-{{ $key }}">
                      {{ $f['label'] }}
                      @if ($f['required']) <span class="req">*</span> @endif
                    </label>
                    <input
                      id="f-{{ $key }}"
                      type="{{ $key === 'no_telp' ? 'tel' : 'text' }}"
                      name="{{ $key }}"
                      value="{{ old($key) }}"
                      maxlength="150"
                      @if (in_array($key, ['no_telp', 'nip_nik'], true)) inputmode="numeric" @endif
                      @if (!empty($f['readonly'])) readonly tabindex="-1" class="is-auto" @endif
                      @if ($f['required']) required @endif
                    >
                    <p class="error-text">{{ $errors->first($key) }}</p>
                  </div>
                @endif
              @endforeach
            @else
              @foreach ($fields as $key => $f)
                <div class="field {{ $key === 'nama' ? 'full' : '' }} {{ $errors->has($key) ? 'has-error' : '' }}">
                  <label for="f-{{ $key }}">
                    {{ $f['label'] }}
                    @if ($f['required']) <span class="req">*</span> @endif
                  </label>
                  <input
                    id="f-{{ $key }}"
                    type="{{ $key === 'no_telp' ? 'tel' : 'text' }}"
                    name="{{ $key }}"
                    value="{{ old($key) }}"
                    maxlength="150"
                    @if (in_array($key, ['no_telp', 'nip_nik'], true)) inputmode="numeric" @endif
                    @if (!empty($f['readonly'])) readonly tabindex="-1" class="is-auto" @endif
                    @if ($f['required']) required @endif
                  >
                  <p class="error-text">{{ $errors->first($key) }}</p>
                </div>
              @endforeach
            @endif

            @if ($punyaTipe)
              <div class="field {{ $errors->has('tipe_peserta') ? 'has-error' : '' }}">
                <label for="tipe_peserta">Tipe Kehadiran Kali Ini <span class="req">*</span></label>
                <select id="tipe_peserta" name="tipe_peserta">
                  <option value="Peserta" @selected(old('tipe_peserta', 'Peserta') === 'Peserta')>Peserta</option>
                  <option value="Narasumber" @selected(old('tipe_peserta') === 'Narasumber')>Narasumber</option>
                </select>
                <p class="error-text">{{ $errors->first('tipe_peserta') }}</p>
              </div>
            @endif
          </div>
        </div>

        {{-- TANDA TANGAN --}}
        <div class="form-section signature-section">
          <div class="section-heading">
            <span class="section-number">02</span>
            <div>
              <h2>Tanda Tangan Digital</h2>
              <p>Bubuhkan tanda tangan Anda pada area di bawah.</p>
            </div>
          </div>

          <div class="field {{ $errors->has('tanda_tangan') ? 'has-error' : '' }}">
            <div class="signature-box">
              <div class="signature-top">
                <span>Area tanda tangan</span>
                <span class="signature-required">Wajib diisi</span>
              </div>
              <div class="sig-wrap">
                <canvas id="sigCanvas"></canvas>
                <div class="sig-footer">
                  <span>Gunakan mouse atau layar sentuh</span>
                  <button type="button" class="sig-clear" id="clearSig">Hapus tanda tangan</button>
                </div>
              </div>
            </div>
            <input type="hidden" name="tanda_tangan" id="tandaTanganInput">
            <p class="error-text" data-errorfor="tanda_tangan">{{ $errors->first('tanda_tangan') }}</p>
          </div>
        </div>

        {{-- KONFIRMASI DATA --}}
        <div class="confirmation-box">
          <input type="checkbox" id="konfirmasi" name="konfirmasi" value="1" @checked(old('konfirmasi'))>
          <label for="konfirmasi">
            <strong>Konfirmasi data</strong>
            <span>Saya menyatakan bahwa data di atas benar dan tanda tangan yang diberikan merupakan tanda tangan saya sendiri.</span>
          </label>
        </div>

        <p class="error-text mt-1 {{ $errors->has('konfirmasi') ? 'show' : '' }}" data-errorfor="konfirmasi">
          {{ $errors->first('konfirmasi') }}
        </p>

        <div class="form-divider"></div>

        {{-- EMAIL SALINAN --}}
        <div class="form-section email-section">
          <div class="section-heading compact">
            <span class="section-number">03</span>
            <div>
              <h2>Salinan Jawaban</h2>
              <p>Opsional. Anda dapat menerima salinan data ke email.</p>
            </div>
          </div>

          <label class="email-toggle" for="kirim_salinan">
            <input type="checkbox" id="kirim_salinan" name="kirim_salinan" value="1" @checked(old('kirim_salinan'))>
            <span>Kirim salinan jawaban ini ke alamat email di bawah.</span>
          </label>

          <div id="emailWrap" class="email-wrap {{ old('kirim_salinan') || $errors->has('email_salinan') ? 'open' : '' }}">
            <div class="field mt-1 {{ $errors->has('email_salinan') ? 'has-error' : '' }}">
              <input
                id="email_salinan"
                type="email"
                name="email_salinan"
                value="{{ old('email_salinan') }}"
                maxlength="150"
                placeholder="nama@email.com"
                autocomplete="email"
                aria-label="Alamat email untuk salinan"
              >
              <p class="hint">Alamat email hanya digunakan untuk mengirim salinan dan tidak disimpan sebagai data absensi.</p>
              <p class="error-text">{{ $errors->first('email_salinan') }}</p>
            </div>
          </div>
        </div>

        {{-- CAPTCHA --}}
        <div class="form-section captcha-section">
          <div class="section-heading compact">
            <span class="section-number">04</span>
            <div>
              <h2>Verifikasi</h2>
              <p>Pastikan pengisian dilakukan oleh manusia.</p>
            </div>
          </div>

          <div class="captcha-card">
            <label for="captcha">Verifikasi keamanan <span class="req">*</span></label>
            <div class="captcha-row">
              <div class="captcha-image">
                <img id="captchaImg" src="{{ route('captcha', ['t' => now()->timestamp]) }}" width="190" height="64" alt="Gambar captcha">
              </div>
              <button type="button" class="captcha-refresh" id="refreshCaptcha" title="Ganti gambar">↻ <span>Ganti kode</span></button>
            </div>
            <input
              id="captcha"
              type="text"
              name="captcha"
              maxlength="10"
              autocomplete="off"
              autocapitalize="characters"
              spellcheck="false"
              placeholder="Ketik kode pada gambar"
              required
            >
            <p class="hint">Masukkan 5 huruf atau angka yang terlihat pada gambar.</p>
            <p class="error-text">{{ $errors->first('captcha') }}</p>
          </div>
        </div>

        {{-- TOMBOL AKSI --}}
        <div class="submit-area">
          <button type="reset" class="btn secondary" id="resetForm">Reset</button>
          <button type="submit" class="btn submit-btn" id="kirimAbsensiBtn">
            <span>Kirim Absensi</span>
            <span class="submit-arrow">→</span>
          </button>
        </div>
      </form>
    </div>

    <div class="attendance-note">
      <span class="note-icon">i</span>
      <p>Pastikan seluruh informasi yang Anda masukkan sudah benar sebelum mengirim absensi.</p>
    </div>
  </div>
</div>
@endsection

{{-- MODAL DILETAKKAN DI LUAR FORM DAN KONTEN UTAMA --}}
@push('modals')
<div id="konfirmasiModal" class="confirm-modal" hidden>
  <div class="confirm-backdrop" data-close-confirm></div>

  <section
    class="confirm-dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirmTitle"
    aria-describedby="confirmMessage"
  >
    <div class="confirm-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="30" height="30" fill="none"
           stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 9v4"></path>
        <path d="M12 17h.01"></path>
        <circle cx="12" cy="12" r="10"></circle>
      </svg>
    </div>

    <h2 id="confirmTitle">Konfirmasi Absensi</h2>

    <p id="confirmMessage">
      Apakah data Anda sudah benar? Pastikan seluruh informasi dan tanda tangan telah sesuai sebelum mengirim absensi.
    </p>

    <div class="confirm-actions">
      <button type="button" class="btn secondary" id="batalKonfirmasi">
        Periksa Kembali
      </button>

      <button type="button" class="btn submit-btn" id="lanjutKonfirmasi">
        Ya, Kirim Absensi
      </button>
    </div>
  </section>
</div>
@endpush

@push('styles')
<style>
#konfirmasiModal[hidden] {
    display: none !important;
}

#konfirmasiModal.confirm-modal {
    position: fixed !important;
    inset: 0 !important;
    z-index: 2147483647 !important;
    display: flex;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    height: 100% !important;
    padding: 20px !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    overflow-y: auto !important;
    isolation: isolate;
}

#konfirmasiModal .confirm-backdrop {
    position: fixed !important;
    inset: 0 !important;
    z-index: 0 !important;
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(4px);
}

#konfirmasiModal .confirm-dialog {
    position: relative !important;
    z-index: 1 !important;
    display: block !important;
    flex: 0 1 460px !important;
    width: 100% !important;
    max-width: 460px !important;
    height: auto !important;
    max-height: calc(100vh - 40px) !important;
    overflow-y: auto !important;
    margin: auto !important;
    padding: 30px !important;
    box-sizing: border-box !important;
    border: 0 !important;
    border-radius: 20px !important;
    background: #fff !important;
    color: #202633 !important;
    text-align: center !important;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28) !important;
    animation: confirmAppear 0.2s ease-out;
}

#konfirmasiModal .confirm-icon {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 58px !important;
    height: 58px !important;
    margin: 0 auto 18px !important;
    border-radius: 50% !important;
    background: #fff1f2 !important;
    color: #9f1d2d !important;
}

#konfirmasiModal .confirm-dialog h2 {
    margin: 0 0 12px !important;
    color: #202633 !important;
    font-size: 23px !important;
}

#konfirmasiModal .confirm-dialog p {
    margin: 0 !important;
    color: #626b78 !important;
    line-height: 1.7 !important;
}

#konfirmasiModal .confirm-actions {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    gap: 12px !important;
    margin-top: 26px !important;
}

#konfirmasiModal .confirm-actions button {
    min-height: 46px;
}

body.modal-open {
    overflow: hidden !important;
}

@keyframes confirmAppear {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 480px) {
    #konfirmasiModal .confirm-dialog {
        padding: 24px 18px !important;
    }

    #konfirmasiModal .confirm-actions {
        flex-direction: column-reverse !important;
    }

    #konfirmasiModal .confirm-actions button {
        width: 100% !important;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/signature_pad.umd.min.js') }}"></script>
<script src="{{ asset('js/absen.js') }}"></script>
@endpush