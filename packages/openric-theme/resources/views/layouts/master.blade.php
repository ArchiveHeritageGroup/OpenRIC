<!DOCTYPE html>
<html lang="{{ $themeData['locale'] ?? 'en' }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $themeData['appName'] ?? 'OpenRiC')</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    {{-- Bootstrap 5 CDN (fallback until Vite build is configured) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YcnS/1WR6zNn36Dg50z6MKnhGGKQ2SUdAel"
          crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    {{-- Vite assets (when build pipeline is ready) --}}
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

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
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>

    @stack('js')
</body>
</html>
