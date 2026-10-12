@extends('layouts.app')
@section('title', 'Ganti Password')

@section('content')
<div class="wrap-xs">
  <div class="card">
    <span class="badge">AKUN SAYA</span>
    <h2>Ganti Password</h2>

    @include('partials.flash')

    <form method="POST" action="{{ route('akun.password.update') }}">
      @csrf
      @method('PUT')
      <div class="field {{ $errors->has('current_password') ? 'has-error' : '' }}">
        <label for="current_password">Password Saat Ini</label>
        <input id="current_password" type="password" name="current_password" maxlength="200" autocomplete="current-password" required>
        <p class="error-text">{{ $errors->first('current_password') }}</p>
      </div>
      <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">Password Baru</label>
        <input id="password" type="password" name="password" maxlength="200" autocomplete="new-password" required>
        <p class="hint">Minimal 10 karakter, ada huruf besar, huruf kecil, dan angka.</p>
        <p class="error-text">{{ $errors->first('password') }}</p>
      </div>
      <div class="field">
        <label for="password_confirmation">Konfirmasi Password Baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" maxlength="200" autocomplete="new-password" required>
      </div>
      <button type="submit" class="btn block">Simpan Password</button>
    </form>
  </div>
</div>
@endsection
