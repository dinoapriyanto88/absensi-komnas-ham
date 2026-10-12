<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title') — Absensi Digital Komnas HAM</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
  <div class="wrap-xs">
    <div class="card center">
      <span class="badge">@yield('code')</span>
      <h2>@yield('title')</h2>
      <p class="desc">@yield('message')</p>
      <a class="btn" href="/">Kembali ke beranda</a>
    </div>
  </div>
</body>
</html>
