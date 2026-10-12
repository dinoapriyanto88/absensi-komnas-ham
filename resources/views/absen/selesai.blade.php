@extends('layouts.public')
@section('title', 'Absensi Tercatat')

@section('content')
<div class="wrap wrap-sm">
  <div class="card done-box">
    <h2>Terima kasih, kehadiran Anda tercatat</h2>

    <p>
      Absensi untuk <strong>{{ $kegiatan->nama }}</strong>
      berhasil dikirim.
    </p>

    @if ($salinanDikirim)
      <p class="small">
        Salinan jawaban sudah dikirim ke email Anda.
        Jika belum tampak, periksa folder spam.
      </p>
    @endif
  </div>
</div>
@endsection