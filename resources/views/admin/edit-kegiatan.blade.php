@extends('layouts.app')
@section('title', 'Edit Kegiatan')
@section('subtitle', 'Masuk sebagai '.auth()->user()->nama.' (Admin)')

@section('content')
<div class="wrap wrap-sm">
  <div class="card">
    <span class="badge">EDIT KEGIATAN</span>
    <h2>{{ $kegiatan->nama }}</h2>
    <p class="desc"><a href="{{ route('admin.index') }}">&larr; Kembali ke dashboard</a></p>

    
<form method="POST"
      action="{{ route('admin.update', $kegiatan) }}"
      data-confirm-action="edit"
      data-confirm="Apakah Anda yakin ingin menyimpan perubahan pada kegiatan ini?">
      @csrf
      @method('PUT')
      @include('admin._form-kegiatan', ['kegiatan' => $kegiatan])
      <button type="submit" class="btn">Simpan Perubahan</button>
    </form>
  </div>
</div>
@endsection
