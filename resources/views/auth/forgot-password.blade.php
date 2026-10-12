@extends('layouts.app')
@section('title', 'Lupa Password')

@section('content')
<div class="wrap-xs">
  <div class="card">
    <span class="badge">LUPA PASSWORD</span>
    <h2>Atur Ulang Password</h2>
    <p class="desc">Masukkan username Anda. Jika akun memiliki email terdaftar, tautan reset akan dikirim ke email tersebut.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('password.email') }}">
      @csrf
      <div class="field {{ $errors->has('username') ? 'has-error' : '' }}">
        <label for="username">Username</label>
        <input id="username" type="text" name="username" value="{{ old('username') }}" maxlength="60"
               autocapitalize="none" required autofocus>
        <p class="error-text">{{ $errors->first('username') }}</p>
      </div>
      <button type="submit" class="btn block">Kirim Tautan Reset</button>
    </form>

    <p class="small muted mt-2">Akun yang tidak punya email dapat meminta Superadmin mereset password-nya.</p>
    <p class="center small"><a href="{{ route('login') }}">Kembali ke login</a></p>
  </div>
</div>
@endsection
