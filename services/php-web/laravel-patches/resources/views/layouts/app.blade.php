<!doctype html>
<html lang="ru" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Space - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    body {
      background: #000000;
      color: #e0e0e0;
      min-height: 100vh;
    }

    .navbar {
      background: #111111 !important;
      border-bottom: 1px solid #333;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
    }

    .navbar-brand {
      font-weight: bold;
      font-size: 1.4rem;
      color: #ffffff !important;
      cursor: default;
    }

    .nav-link {
      color: #a0a0ff !important;
      font-weight: 500;
      transition: color 0.3s ease, transform 0.2s ease;
    }

    .nav-link:hover {
      color: #ffffff !important;
      transform: translateY(-2px);
    }

    .nav-link.active {
      color: #ffffff !important;
      font-weight: bold;
    }

    @keyframes fadeUpSmooth {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-in {
      animation: fadeUpSmooth 0.8s ease-out both;
    }

    .fade-in-delay-1 { animation-delay: 0.15s; }
    .fade-in-delay-2 { animation-delay: 0.3s; }
    .fade-in-delay-3 { animation-delay: 0.45s; }
    .fade-in-delay-4 { animation-delay: 0.6s; }

    #map { height: 340px; border-radius: 12px; }

    th[data-sort]::after { content: '⇅'; opacity: 0.4; margin-left: 0.4em; }
    th[data-dir="asc"]::after { content: '↑'; opacity: 1; }
    th[data-dir="desc"]::after { content: '↓'; opacity: 1; }
  </style>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <nav class="navbar navbar-expand-lg mb-4 py-3">
    <div class="container">
      <span class="navbar-brand">Space Dashboard</span>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <div class="navbar-nav ms-auto gap-3">
          <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">Dashboard</a>
          <a class="nav-link {{ request()->is('astro') ? 'active' : '' }}" href="/astro">AstronomyAPI</a>
          <a class="nav-link {{ request()->is('iss') ? 'active' : '' }}" href="/iss">ISS</a>
          <a class="nav-link {{ request()->is('osdr') ? 'active' : '' }}" href="/osdr">OSDR</a>
          <a class="nav-link {{ request()->is('telemetry') ? 'active' : '' }}" href="/telemetry">Telemetry</a>
          <a class="nav-link {{ request()->is('cms') ? 'active' : '' }}" href="/cms">CMS</a>
        </div>
      </div>
    </div>
  </nav>

  <main class="container pb-5">
    @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>