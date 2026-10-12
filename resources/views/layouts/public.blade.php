<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#9b1c2e">

    <title>@yield('title', 'Absensi Digital') — Komnas HAM</title>

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('img/logo-komnas-ham.png') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/app.css') }}"
    >
</head>

<body>

    <!-- =====================================================
         HEADER
         ===================================================== -->
    <header class="topbar">
        <div class="topbar-inner">

            <a
                class="brand"
                href="{{ route('absen.index') }}"
            >

                <img
                    class="brand-logo"
                    src="{{ asset('img/logo-komnas-ham.png') }}"
                    alt="Logo Komnas HAM"
                >

                <span class="brand-text">
                    <strong>
                        Komisi Nasional Hak Asasi Manusia
                    </strong>

                    <small>
                        @yield('subtitle', 'Sistem Kehadiran Digital')
                    </small>
                </span>

            </a>

        </div>
    </header>


    <!-- =====================================================
         CONTENT
         ===================================================== -->
    <main>
        @yield('content')
    </main>


    <!-- =====================================================
         POPUP (MODAL)
         Harus dicetak SEBELUM script, supaya popup sudah ada
         di halaman saat absen.js dijalankan.
         ===================================================== -->
    @stack('modals')


    <!-- =====================================================
         JAVASCRIPT
         ===================================================== -->
    <script
        src="{{ asset('js/app.js') }}"
        defer
    ></script>

    @stack('scripts')

</body>
</html>