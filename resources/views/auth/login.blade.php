@extends('layouts.app')

@section('title', 'Login Admin')

@section('body-class', 'login-page')

@section('main-class', 'login-main')

@section('content')

<div class="login-screen">

  {{-- BAGIAN KIRI --}}
  <section class="login-left">

    <div class="login-content">

      {{-- LOGO --}}
      <div class="login-brand">

        <img
          src="{{ asset('img/logo-komnas-ham.png') }}"
          alt="Logo Komnas HAM"
          class="login-logo"
        >

        <div class="login-brand-text">
          <strong>Komisi Nasional Hak Asasi Manusia</strong>
          <span>Sistem Kehadiran Digital</span>
        </div>

      </div>


      {{-- JUDUL --}}
      <div class="login-heading">

        <span class="login-badge">
          PORTAL ADMIN
        </span>

        <h1>
          Selamat Datang!
        </h1>

        <p>
          Silakan masuk untuk mengelola kegiatan, memantau kehadiran,
          dan mengelola data peserta pada Sistem Kehadiran Digital
          Komnas HAM.
        </p>

      </div>


      {{-- FLASH MESSAGE --}}
      @include('partials.flash')


      {{-- FORM LOGIN --}}
      <form
        method="POST"
        action="{{ route('login') }}"
        class="login-form"
      >

        @csrf


        {{-- USERNAME --}}
        <div class="login-field {{ $errors->has('username') ? 'has-error' : '' }}">

          <label for="username">
            Username
          </label>

          <input
            id="username"
            type="text"
            name="username"
            value="{{ old('username') }}"
            maxlength="60"
            autocomplete="username"
            autocapitalize="none"
            placeholder="Masukkan username"
            required
            autofocus
          >

          <p class="error-text">
            {{ $errors->first('username') }}
          </p>

        </div>


        {{-- PASSWORD --}}
        <div class="login-field {{ $errors->has('password') ? 'has-error' : '' }}">

          <div class="password-label">

            <label for="password">
              Password
            </label>

            <a href="{{ route('password.request') }}">
              Lupa password?
            </a>

          </div>

          <input
            id="password"
            type="password"
            name="password"
            maxlength="200"
            autocomplete="current-password"
            placeholder="Masukkan password"
            required
          >

          <p class="error-text">
            {{ $errors->first('password') }}
          </p>

        </div>


        {{-- TOMBOL --}}
        <button
          type="submit"
          class="login-button"
        >
          <span>MASUK</span>
          <span class="login-arrow">→</span>
        </button>

      </form>

    </div>

  </section>


  {{-- BAGIAN KANAN --}}
  <section class="login-right">

    <div class="right-decoration decoration-one"></div>
    <div class="right-decoration decoration-two"></div>

    <div class="login-right-content">

      <span class="right-badge">
        SISTEM KEHADIRAN DIGITAL
      </span>


      {{-- LOGO BESAR --}}
      <img
        src="{{ asset('img/logo-komnas-ham.png') }}"
        alt="Logo Komnas HAM"
        class="right-logo"
      >


      <h2>
        Kelola Kehadiran<br>
        dengan Lebih Mudah
      </h2>

      <p>
        Kelola kegiatan, data peserta, dan rekap kehadiran
        secara terpusat melalui sistem digital Komnas HAM.
      </p>


      {{-- FITUR --}}
      <div class="login-features">

        <div class="feature-box">
          <strong>01</strong>
          <span>Kelola<br>Kegiatan</span>
        </div>

        <div class="feature-box">
          <strong>02</strong>
          <span>Data<br>Peserta</span>
        </div>

        <div class="feature-box">
          <strong>03</strong>
          <span>Rekap<br>Kehadiran</span>
        </div>

      </div>

    </div>

  </section>

</div>

@endsection