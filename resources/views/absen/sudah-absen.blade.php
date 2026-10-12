```blade
@extends('layouts.public')
@section('title', 'Sudah Absen')

@section('content')
<div class="wrap wrap-sm">
  <div class="card done-box">
    <h2>Anda sudah tercatat hadir</h2>

    <p>
      Kehadiran Anda untuk <strong>{{ $kegiatan->nama }}</strong>
      sudah ada. Absensi hanya bisa dilakukan satu kali per kegiatan.
    </p>
  </div>
</div>
@endsection
```
