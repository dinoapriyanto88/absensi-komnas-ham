<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#a61932">

  <title>@yield('title', 'Absensi Digital') — Komnas HAM</title>

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="@yield('body-class')">

  {{-- HEADER --}}
  @if (!request()->routeIs('login'))
    <div class="topbar">
      <div class="topbar-inner">
        <a class="brand"
           href="{{ auth()->check() ? route('dashboard') : route('absen.index') }}">

          <img
            class="brand-logo"
            src="{{ asset('img/logo-komnas-ham.png') }}"
            alt="Logo Komnas HAM"
          >

          <span class="brand-text">
            <strong>Komisi Nasional Hak Asasi Manusia</strong>
            <small>Sistem Kehadiran Digital</small>
          </span>
        </a>

        @auth
          <nav class="topnav">
            <a href="{{ route('akun.password.edit') }}">
              Ganti Password
            </a>

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit">Keluar</button>
            </form>
          </nav>
        @endauth
      </div>
    </div>
  @endif

  {{-- ISI HALAMAN --}}
  <main class="@yield('main-class')">
    @yield('content')
  </main>

  {{-- FOOTER --}}
  @if (!request()->routeIs('login'))
    <footer class="footer">
      &copy; {{ date('Y') }}
      Komisi Nasional Hak Asasi Manusia Republik Indonesia
    </footer>
  @endif

  {{-- MODAL KONFIRMASI TINDAKAN ADMIN --}}
  <div id="adminConfirmModal"
       class="admin-confirm-modal"
       aria-hidden="true">

    <div class="admin-confirm-backdrop" data-confirm-cancel></div>

    <section class="admin-confirm-dialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="adminConfirmTitle"
             aria-describedby="adminConfirmMessage"
             tabindex="-1">

      <div class="admin-confirm-icon" id="adminConfirmIcon">?</div>

      <h2 id="adminConfirmTitle">Konfirmasi Tindakan</h2>

      <p id="adminConfirmMessage">
        Apakah Anda yakin ingin melanjutkan tindakan ini?
      </p>

      <div class="admin-confirm-actions">
        <button type="button"
                class="admin-confirm-cancel"
                data-confirm-cancel>
          Batal
        </button>

        <button type="button"
                id="adminConfirmProceed"
                class="admin-confirm-proceed">
          Ya, Lanjutkan
        </button>
      </div>

    </section>
  </div>

  {{-- JAVASCRIPT: tiap berkas cukup SATU kali --}}
  <script src="{{ asset('js/app.js') }}" defer></script>
  <script src="{{ asset('js/admin-confirm.js') }}" defer></script>

  @stack('scripts')

</body>
</html>