#!/usr/bin/env php
<?php
/**
 * OpenRiC — Audit All URLs
 *
 * Validates every route('name') call in all OpenRiC blade files against
 * the registered route list from `php artisan route:list --json`.
 *
 * Reports:
 *   - Summary table: files scanned, hrefs found, route() calls, valid routes,
 *     broken routes, blade expressions, static URLs, external URLs, anchors
 *   - List of broken routes with file path and route name
 *   - Deduplication of issues
 *
 * Usage:
 *   php bin/audit-all-urls.php
 *   php bin/audit-all-urls.php --verbose
 */

$base = '/usr/share/nginx/OpenRiC/packages';
$verbose = in_array('--verbose', $argv);

echo "========================================\n";
echo "  OpenRiC — Audit All URLs\n";
echo "  Scanning: {$base}\n";
echo "========================================\n\n";

// ──────────────────────────────────────────────
// 1. Load registered route names
// ──────────────────────────────────────────────
echo "[1/3] Loading registered routes ...\n";

$registeredRoutes = [];

// Try JSON output first
$jsonOutput = shell_exec('php ' . __DIR__ . '/lib/route-extract.php --app /usr/share/nginx/OpenRiC 2>/dev/null');
$decoded = json_decode($jsonOutput ?: '', true);

if (is_array($decoded) && count($decoded) > 0) {
    foreach ($decoded as $route) {
        $name = $route['name'] ?? null;
        if ($name !== null && $name !== '') {
            $registeredRoutes[$name] = [
                'method' => $route['method'] ?? '?',
                'uri'    => $route['uri'] ?? '?',
                'action' => $route['action'] ?? '?',
            ];
        }
    }
    echo "  Loaded " . count($registeredRoutes) . " named routes (JSON)\n";
} else {
    // Fallback: parse text output
    $textOutput = shell_exec('php ' . __DIR__ . '/lib/route-extract.php --app /usr/share/nginx/OpenRiC 2>/dev/null') ?: '';
    $lines = explode("\n", $textOutput);
    foreach ($lines as $line) {
        // Text output format: METHOD | URI | NAME | ACTION
        if (preg_match('/\|\s*(\S+)\s*\|\s*(\S+)\s*\|\s*(\S+)\s*\|/', $line, $m)) {
            $name = trim($m[3]);
            if ($name && $name !== 'Name' && $name !== '') {
                $registeredRoutes[$name] = [
                    'method' => trim($m[1]),
                    'uri'    => trim($m[2]),
                    'action' => '',
                ];
            }
        }
    }
    echo "  Loaded " . count($registeredRoutes) . " named routes (text fallback)\n";
}

if (count($registeredRoutes) === 0) {
    echo "  WARNING: No routes loaded. All route() calls will be reported as broken.\n";
    echo "  Make sure 'php artisan route:list' works from /usr/share/nginx/OpenRiC\n\n";
}

// ──────────────────────────────────────────────
// 2. Scan all blade files
// ──────────────────────────────────────────────
echo "[2/3] Scanning blade files ...\n";

$bladeFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
foreach ($rii as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $bladeFiles[] = $file->getPathname();
    }
}
sort($bladeFiles);

echo "  Found " . count($bladeFiles) . " blade files\n\n";

// Counters
$totalFilesScanned  = 0;
$totalHrefs         = 0;
$totalRouteCalls    = 0;
$totalValidRoutes   = 0;
$totalBrokenRoutes  = 0;
$totalBladeExprs    = 0;  // {{ $var }}, {!! ... !!}
$totalStaticUrls    = 0;  // /path/to/something
$totalExternalUrls  = 0;  // http:// or https://
$totalAnchors       = 0;  // #something
$totalUrlCalls      = 0;  // url() helper calls
$totalOther         = 0;

$brokenRouteDetails = [];  // [file, routeName]
$seenBroken         = [];  // dedup key => true

// ──────────────────────────────────────────────
// 3. Process each file
// ──────────────────────────────────────────────
echo "[3/3] Analysing ...\n";

foreach ($bladeFiles as $filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) continue;

    $totalFilesScanned++;
    $relPath = str_replace($base . '/', '', $filePath);

    // ── Extract all href values ──
    // Matches href="..." and href='...'
    if (preg_match_all('/href\s*=\s*["\']([^"\']*?)["\']/i', $content, $hrefMatches)) {
        $totalHrefs += count($hrefMatches[1]);

        foreach ($hrefMatches[1] as $href) {
            $href = trim($href);

            if ($href === '' || $href === '#') {
                $totalAnchors++;
                continue;
            }
            if (str_starts_with($href, '#')) {
                $totalAnchors++;
                continue;
            }
            if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                $totalExternalUrls++;
                continue;
            }
            if (str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                $totalOther++;
                continue;
            }
            if (str_contains($href, '{{') || str_contains($href, '{!!')) {
                $totalBladeExprs++;
                continue;
            }
            // Static internal URLs (start with /)
            if (str_starts_with($href, '/')) {
                $totalStaticUrls++;
                continue;
            }
            $totalOther++;
        }
    }

    // ── Also match href="{{ route(...) }}" and href="{{ url(...) }}" ──
    // These are Blade expressions inside href attributes
    if (preg_match_all('/href\s*=\s*["\']\{\{\s*(route|url)\s*\(/', $content, $exprMatches)) {
        // Already counted in blade exprs above; just note they exist
    }

    // ── Extract all route('name') calls ──
    // Matches route('name'), route('name', ...), route("name"), route("name", ...)
    if (preg_match_all("/route\(\s*['\"]([^'\"]+)['\"]/", $content, $routeMatches)) {
        foreach ($routeMatches[1] as $routeName) {
            $totalRouteCalls++;

            if (isset($registeredRoutes[$routeName])) {
                $totalValidRoutes++;
            } else {
                $totalBrokenRoutes++;

                $dedupKey = $routeName . '||' . $relPath;
                if (!isset($seenBroken[$dedupKey])) {
                    $seenBroken[$dedupKey] = true;
                    $brokenRouteDetails[] = [
                        'file'  => $relPath,
                        'route' => $routeName,
                    ];
                }
            }
        }
    }

    // ── Count url() calls ──
    if (preg_match_all("/url\(\s*['\"]([^'\"]+)['\"]/", $content, $urlMatches)) {
        $totalUrlCalls += count($urlMatches[1]);
    }
}

// ──────────────────────────────────────────────
// Summary
// ──────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════╗\n";
echo "║              URL AUDIT SUMMARY                  ║\n";
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  Files scanned        %'.-20d     ║\n", $totalFilesScanned);
printf("║  Total href values    %'.-20d     ║\n", $totalHrefs);
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  route() calls        %'.-20d     ║\n", $totalRouteCalls);
printf("║    Valid routes        %'.-20d    ║\n", $totalValidRoutes);
printf("║    BROKEN routes       %'.-20d    ║\n", $totalBrokenRoutes);
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  url() calls          %'.-20d     ║\n", $totalUrlCalls);
printf("║  Blade expressions    %'.-20d     ║\n", $totalBladeExprs);
printf("║  Static URLs (/...)   %'.-20d     ║\n", $totalStaticUrls);
printf("║  External URLs        %'.-20d     ║\n", $totalExternalUrls);
printf("║  Anchors (#)          %'.-20d     ║\n", $totalAnchors);
printf("║  Other                %'.-20d     ║\n", $totalOther);
echo "╚══════════════════════════════════════════════════╝\n";

// ──────────────────────────────────────────────
// Broken routes detail
// ──────────────────────────────────────────────
if (count($brokenRouteDetails) > 0) {
    // Deduplicate by route name for the summary
    $byRouteName = [];
    foreach ($brokenRouteDetails as $entry) {
        $byRouteName[$entry['route']][] = $entry['file'];
    }

    echo "\n";
    echo "=== BROKEN ROUTES (" . count($brokenRouteDetails) . " occurrences, " . count($byRouteName) . " unique names) ===\n\n";

    // Sort by route name
    ksort($byRouteName);

    foreach ($byRouteName as $routeName => $files) {
        echo "  BROKEN  route('{$routeName}')\n";
        $uniqueFiles = array_unique($files);
        sort($uniqueFiles);
        foreach ($uniqueFiles as $f) {
            echo "          in {$f}\n";
        }
    }

    echo "\n";
} else {
    echo "\n  All route() calls resolve to registered routes.\n\n";
}

// ──────────────────────────────────────────────
// Verbose: list all registered routes
// ──────────────────────────────────────────────
if ($verbose && count($registeredRoutes) > 0) {
    echo "=== REGISTERED ROUTES (" . count($registeredRoutes) . ") ===\n";
    ksort($registeredRoutes);
    foreach ($registeredRoutes as $name => $info) {
        printf("  %-40s  %s  %s\n", $name, $info['method'], $info['uri']);
    }
    echo "\n";
}

// Exit code: 1 if broken routes found
exit(count($brokenRouteDetails) > 0 ? 1 : 0);
