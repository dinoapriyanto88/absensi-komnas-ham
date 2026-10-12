@extends('layouts.app')
@section('title', 'Password Baru')

@section('content')
<div class="wrap-xs">
  <div class="card">
    <span class="badge">PASSWORD BARU</span>
    <h2>Buat Password Baru</h2>

    @if ($errors->has('email'))
      <div class="alert error">{{ $errors->first('email') }}</div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="hidden" name="email" value="{{ old('email', $email) }}">

      <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">Password Baru</label>
        <input id="password" type="password" name="password" maxlength="200" autocomplete="new-password" required autofocus>
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
