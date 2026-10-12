@extends('layouts.app')
@section('title', 'Kelola Admin')
@section('subtitle', 'Masuk sebagai '.auth()->user()->nama.' (Superadmin)')

@section('content')
<div class="wrap">
  @include('partials.flash')

  @if ($errors->has('admin'))
    <div class="alert error">{{ $errors->first('admin') }}</div>
  @endif

  @if (session('password_sementara'))
    <div class="alert info">
      Password sementara untuk <strong>{{ session('password_sementara_untuk') }}</strong>
      (hanya tampil sekali, sampaikan lewat jalur aman):
      <div class="mt-1"><code class="password-box">{{ session('password_sementara') }}</code></div>
      <div class="small mt-1">Admin akan diwajibkan menggantinya saat login berikutnya.</div>
    </div>
  @endif

  <div class="card">
    <span class="badge">AKUN ADMIN</span>
    <h2>Kelola Admin</h2>
    <p class="desc">Superadmin hanya membuat dan memantau akun Admin. Kegiatan dan rekap adalah tugas Admin.</p>

    @if ($admins->isEmpty())
      <div class="empty-state">Belum ada akun admin.</div>
    @else
      <div class="table-scroll">
        <table>
          <thead>
            <tr><th>Nama</th><th>Username</th><th>Email</th><th>Kegiatan</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
          @foreach ($admins as $a)
            <tr @if (! $a->is_active) style="opacity:.65" @endif>
              <td><strong>{{ $a->nama }}</strong></td>
              <td>{{ $a->username }}</td>
              <td>{{ $a->email ?: '-' }}</td>
              <td>{{ $a->kegiatan_count }}</td>
              <td>
                <span class="status-pill {{ $a->is_active ? 'active' : 'closed' }}" style="cursor:default">
                  <span class="status-dot"></span>{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="nowrap">
                <form class="inline-form" method="POST" action="{{ route('superadmin.aktif', $a) }}"
                      data-confirm="{{ $a->is_active
                        ? 'Nonaktifkan akun '.$a->username.'? Admin langsung keluar dari semua perangkat dan tidak bisa login. Kegiatan dan datanya tetap tersimpan.'
                        : 'Aktifkan kembali akun '.$a->username.'?' }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn small {{ $a->is_active ? 'secondary' : '' }}">
                    {{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                  </button>
                </form>

                <form class="inline-form" method="POST" action="{{ route('superadmin.reset', $a) }}"
                      data-confirm="Buat password sementara baru untuk {{ $a->username }}? Password lamanya langsung tidak berlaku.">
                  @csrf
                  <button type="submit" class="btn small secondary">Reset Password</button>
                </form>

                <form class="inline-form" method="POST" action="{{ route('superadmin.destroy', $a) }}"
                      data-confirm="Hapus akun admin {{ $a->username }}?">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn small danger">Hapus</button>
                </form>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <details class="mt-3" @if ($admins->isEmpty() || $errors->any()) open @endif>
      <summary><strong>+ Buat Akun Admin</strong></summary>
      <form method="POST" action="{{ route('superadmin.store') }}" class="mt-2">
        @csrf
        <div class="grid-2">
          <div class="field {{ $errors->has('nama') ? 'has-error' : '' }}">
            <label for="nama">Nama <span class="req">*</span></label>
            <input id="nama" type="text" name="nama" value="{{ old('nama') }}" maxlength="150" required>
            <p class="error-text">{{ $errors->first('nama') }}</p>
          </div>
          <div class="field {{ $errors->has('username') ? 'has-error' : '' }}">
            <label for="username">Username <span class="req">*</span></label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" maxlength="30" autocapitalize="none" required>
            <p class="error-text">{{ $errors->first('username') }}</p>
          </div>
        </div>
        <div class="grid-2">
          <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
            <label for="email">Email <span class="req">*</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" required>
            <p class="hint">Notifikasi akun baru dikirim ke email ini.</p>
            <p class="error-text">{{ $errors->first('email') }}</p>
          </div>
          <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
            <label for="password">Password Awal <span class="req">*</span></label>
            <input id="password" type="password" name="password" maxlength="200" autocomplete="new-password" required>
            <p class="hint">Minimal 10 karakter, huruf besar, huruf kecil, angka. Admin wajib menggantinya saat login pertama.</p>
            <p class="error-text">{{ $errors->first('password') }}</p>
          </div>
        </div>
        <button type="submit" class="btn">Buat Akun Admin</button>
      </form>
    </details>
  </div>
</div>
@endsection