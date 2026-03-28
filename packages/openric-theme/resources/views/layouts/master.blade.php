<!DOCTYPE html>
<html lang="{{ $themeData['locale'] ?? 'en' }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $themeData['appName'] ?? 'OpenRiC')</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('OpenRiC.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('OpenRiC.png') }}">

    {{-- Bootstrap 5 CDN (fallback until Vite build is configured) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    {{-- Vite assets (when build pipeline is ready) --}}
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        /* === OpenRiC Brand === */
        :root {
            --ric-primary: #1a5276;
            --ric-secondary: #2e86c1;
            --ric-accent: #17a2b8;
            --ric-dark: #1c2833;
            --ric-light: #f8f9fa;
            --ric-sidebar-bg: #f4f6f9;
            --ric-border: #dee2e6;
        }

        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f0f2f5; color: #333; }

        /* === Navbar === */
        .navbar { background: linear-gradient(135deg, var(--ric-dark) 0%, var(--ric-primary) 100%) !important; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .navbar-brand img { filter: brightness(1.1); }
        .navbar .nav-link { font-size: 0.9rem; font-weight: 500; letter-spacing: 0.02em; }
        .navbar .dropdown-menu { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.12); border-radius: 0.5rem; }

        /* === Content area === */
        main { background: transparent; }
        .container-xl { max-width: 1400px; }

        /* === Cards === */
        .card { border: 1px solid var(--ric-border); border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header { background: var(--ric-light); border-bottom: 1px solid var(--ric-border); font-weight: 600; }

        /* === Tables === */
        .table { font-size: 0.9rem; }
        .table th { font-weight: 600; color: var(--ric-primary); text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.04em; border-bottom-width: 2px; }
        .table td { vertical-align: middle; }
        .table-hover tbody tr:hover { background-color: rgba(26, 82, 118, 0.04); }

        /* === Sidebar === */
        #sidebar { background: var(--ric-sidebar-bg); border-right: 1px solid var(--ric-border); border-radius: 0.5rem; padding: 0; min-height: calc(100vh - 200px); }
        .sidebar-nav .accordion-button { padding: 0.65rem 1rem; font-size: 0.88rem; font-weight: 600; color: var(--ric-dark); background: transparent; }
        .sidebar-nav .accordion-button:not(.collapsed) { background: rgba(26, 82, 118, 0.08); color: var(--ric-primary); box-shadow: none; }
        .sidebar-nav .accordion-button:focus { box-shadow: none; }
        .sidebar-nav .accordion-button::after { width: 0.9rem; height: 0.9rem; background-size: 0.9rem; }
        .sidebar-nav .accordion-item { border: none; background: transparent; }
        .sidebar-nav .accordion-body { padding: 0; }
        .sidebar-nav .accordion-body .list-group-item { padding: 0.5rem 1rem 0.5rem 2.4rem; font-size: 0.84rem; border: none; background: transparent; color: #555; transition: all 0.15s; }
        .sidebar-nav .accordion-body .list-group-item:hover { background: rgba(26, 82, 118, 0.06); color: var(--ric-primary); padding-left: 2.6rem; }
        .sidebar-nav .accordion-body .list-group-item.active { background: var(--ric-primary); color: #fff; border-radius: 0.25rem; margin: 0.1rem 0.5rem; padding-left: 2rem; }
        .sidebar-nav .accordion-body .list-group-item i { opacity: 0.7; }
        .sidebar-nav .accordion-body .list-group-item.active i { opacity: 1; }

        /* === Buttons === */
        .btn-primary { background: var(--ric-primary); border-color: var(--ric-primary); }
        .btn-primary:hover { background: var(--ric-secondary); border-color: var(--ric-secondary); }
        .btn-outline-primary { color: var(--ric-primary); border-color: var(--ric-primary); }
        .btn-outline-primary:hover { background: var(--ric-primary); color: #fff; }
        .btn { font-size: 0.88rem; font-weight: 500; border-radius: 0.4rem; }

        /* === Badges === */
        .badge { font-weight: 500; font-size: 0.78rem; letter-spacing: 0.02em; }

        /* === Forms === */
        .form-label { font-weight: 600; font-size: 0.88rem; color: #444; margin-bottom: 0.3rem; }
        .form-control, .form-select { border-radius: 0.4rem; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus { border-color: var(--ric-secondary); box-shadow: 0 0 0 0.2rem rgba(46, 134, 193, 0.15); }

        /* === Headings === */
        h1, .h1 { color: var(--ric-dark); font-weight: 700; }
        h2, .h2, h3, .h3 { color: var(--ric-primary); font-weight: 600; }

        /* === Footer === */
        footer { background: var(--ric-dark) !important; border-top: 3px solid var(--ric-secondary); }

        /* === Login page === */
        .card.shadow-sm { border-radius: 0.75rem; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important; }

        /* === View switch === */
        .btn-group .btn-info { background: var(--ric-accent); border-color: var(--ric-accent); }
        .btn-group .btn-outline-info { color: var(--ric-accent); border-color: var(--ric-accent); }
        .btn-group .btn-outline-info:hover { background: var(--ric-accent); color: #fff; }

        /* === Breadcrumbs === */
        .breadcrumb { font-size: 0.85rem; }

        /* === Code blocks === */
        code { font-size: 0.82rem; color: var(--ric-primary); }

        /* === Property display (entity show) === */
        dt { font-weight: 600; color: #555; font-size: 0.88rem; }
        dd { font-size: 0.9rem; }

        /* === Print === */
        @media print {
            .navbar, footer, #sidebar, .btn-group, .btn { display: none !important; }
            main { padding: 0 !important; }
            .card { border: none; box-shadow: none; }
        }
    </style>
    @stack('css')
</head>
<body class="d-flex flex-column min-vh-100 @yield('body-class')">

    {{-- Skip to main content (WCAG 2.1 AA) --}}
    <a class="visually-hidden-focusable position-absolute top-0 start-0 p-3 bg-primary text-white z-3"
       href="#main-content">
        Skip to main content
    </a>

    {{-- Header / Navigation --}}
    @include('theme::partials.header')

    {{-- Admin notifications (pending requests, failed jobs) --}}
    @include('theme::partials.admin-notifications')

    {{-- Accessibility helpers (ARIA live region, keyboard nav, focus management) --}}
    @include('theme::partials.accessibility-helpers')

    {{-- Flash message alerts --}}
    <div class="container-xl mt-3">
        @include('theme::partials.alerts')
    </div>

    {{-- Breadcrumbs --}}
    @hasSection('breadcrumbs')
        <div class="container-xl mt-2">
            @include('theme::partials.breadcrumbs')
        </div>
    @endif

    {{-- Main content area --}}
    <main id="main-content" role="main" class="container-xl flex-grow-1 py-3">
        @yield('layout-content')
    </main>

    {{-- Footer --}}
    @include('theme::partials.footer')

    {{-- Bootstrap 5 JS CDN (fallback until Vite build is configured) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            crossorigin="anonymous"></script>

    @stack('js')
</body>
</html>
