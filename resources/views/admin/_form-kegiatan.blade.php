@php
    // Nilai awal field: input lama (jika validasi gagal) -> nilai kegiatan -> kosong.
    $f = function (string $key, ?string $format = null) use ($kegiatan) {
        $nilai = $kegiatan?->{$key};
        if ($format !== null) {
            $nilai = $nilai?->format($format);
        }

        return old($key, $nilai);
    };
@endphp
<div class="field {{ $errors->has('nama') ? 'has-error' : '' }}">
  <label for="nama">Nama Kegiatan <span class="req">*</span></label>
  <input id="nama" type="text" name="nama" value="{{ $f('nama') }}" maxlength="150" required>
  <p class="error-text">{{ $errors->first('nama') }}</p>
</div>
<div class="field {{ $errors->has('deskripsi') ? 'has-error' : '' }}">
  <label for="deskripsi">Deskripsi</label>
  <input id="deskripsi" type="text" name="deskripsi" value="{{ $f('deskripsi') }}" maxlength="500">
  <p class="error-text">{{ $errors->first('deskripsi') }}</p>
</div>
<p class="small mb-0"><strong>Batas Waktu Pengisian (opsional)</strong></p>
<p class="hint">Kosongkan jika tidak ingin dibatasi waktu. Waktu mengikuti zona waktu server ({{ config('app.timezone') }}).</p>
<div class="grid-2 mt-1">
  <div class="field {{ $errors->has('mulai_at') ? 'has-error' : '' }}">
    <label for="mulai_at">Mulai</label>
    <input id="mulai_at" type="datetime-local" name="mulai_at" value="{{ $f('mulai_at', 'Y-m-d\TH:i') }}">
    <p class="error-text">{{ $errors->first('mulai_at') }}</p>
  </div>
  <div class="field {{ $errors->has('selesai_at') ? 'has-error' : '' }}">
    <label for="selesai_at">Selesai</label>
    <input id="selesai_at" type="datetime-local" name="selesai_at" value="{{ $f('selesai_at', 'Y-m-d\TH:i') }}">
    <p class="error-text">{{ $errors->first('selesai_at') }}</p>
  </div>
</div>