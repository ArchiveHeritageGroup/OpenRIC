<!DOCTYPE html>
<html lang="{{ $themeData['locale'] ?? 'en' }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $themeData['appName'] ?? 'OpenRiC') - Print</title>

    {{-- Bootstrap 5 CDN for print styling --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YcnS/1WR6zNn36Dg50z6MKnhGGKQ2SUdAel"
          crossorigin="anonymous">

    <style>
        @media print {
            body { font-size: 12pt; }
            .no-print { display: none !important; }
            a[href]:after { content: " (" attr(href) ")"; font-size: 0.8em; }
        }
    </style>
    @stack('css')
</head>
<body>
    <div class="container py-3">
        <header class="mb-4 border-bottom pb-2">
            <h1 class="h4">{{ $themeData['appName'] ?? 'OpenRiC' }}</h1>
            <p class="text-muted small mb-0">
                Printed on {{ now()->format('Y-m-d H:i') }}
            </p>
        </header>

        <main id="main-content" role="main">
            @yield('content')
        </main>

        <footer class="mt-4 pt-2 border-top text-muted small">
            <p>&copy; {{ date('Y') }} {{ $themeData['appName'] ?? 'OpenRiC' }}</p>
        </footer>
    </div>

    <div class="container no-print my-3">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1" aria-hidden="true"></i> Print this page
        </button>
        <a href="javascript:history.back()" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go back
        </a>
    </div>

    @stack('js')
</body>
</html>
