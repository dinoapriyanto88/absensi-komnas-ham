@extends('layouts.app')
@section('title', 'Rekap Kehadiran')
@section('subtitle', 'Masuk sebagai '.auth()->user()->nama.' (Admin)')

@section('content')
<div class="wrap">
  <div class="card">
    <span class="badge">REKAP KEHADIRAN</span>
    <h2>{{ $kegiatan->nama }}</h2>
    <p class="desc"><a href="{{ route('admin.index') }}">&larr; Kembali ke dashboard</a> &middot; Total: {{ $kehadiran->total() }} peserta</p>

    <div class="alert info">Tautan langsung absensi kegiatan ini: <a href="{{ $linkLangsung }}" target="_blank" rel="noopener noreferrer">{{ $linkLangsung }}</a></div>

    <div class="actions-row">
      <a class="btn" href="{{ route('admin.export.pdf', $kegiatan) }}">Unduh PDF (dengan tanda tangan)</a>
      <a class="btn secondary" href="{{ route('admin.export.csv', $kegiatan) }}">Unduh CSV</a>
    </div>

    <form class="search-box" method="GET" action="{{ route('admin.show', $kegiatan) }}">
      <input type="text" name="q" value="{{ $q }}" maxlength="100" placeholder="Cari nama, instansi, atau unit kerja">
      <button type="submit" class="btn small">Cari</button>
    </form>

    @if ($kehadiran->isEmpty())
      <div class="empty-state">Belum ada data kehadiran.</div>
    @else
      <div class="table-scroll">
        <table>
          <thead><tr><th>Nama</th><th>Jenis</th><th>Jabatan</th><th>Instansi / Unit</th><th>NIP/NIK</th><th>No. Telepon</th><th>Tanda Tangan</th><th>Waktu</th></tr></thead>
          <tbody>
          @foreach ($kehadiran as $k)
            <tr>
              <td><strong>{{ $k->nama }}</strong>@if ($k->tipe_peserta)<br><span class="small muted">{{ $k->tipe_peserta }}</span>@endif</td>
              <td><span class="tag {{ $k->jenis_peserta }}">{{ ucfirst($k->jenis_peserta) }}</span></td>
              <td>{{ $k->jabatan ?: '-' }}</td>
              <td>{{ $k->instansi ?: ($k->unit_kerja ?: '-') }}</td>
              <td>{{ $k->nip_nik ?: '-' }}</td>
              <td class="small">{{ $k->no_telp ?: '-' }}</td>
              <td><img class="ttd-thumb" loading="lazy" alt="Tanda tangan {{ $k->nama }}" src="{{ route('admin.tanda-tangan', [$kegiatan, $k]) }}"></td>
              <td class="small nowrap">{{ $k->created_at->translatedFormat('j M Y') }}<br>{{ $k->created_at->format('H:i') }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      {{ $kehadiran->links('pagination::default') }}
    @endif
  </div>
</div>
@endsection
