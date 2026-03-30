#!/usr/bin/env php
<?php
/**
 * OpenRiC — Missing Views Audit
 *
 * Scans all Heratio blade templates across all packages and checks if a
 * corresponding OpenRiC blade exists.  Reports missing views by package
 * and identifies packages without OpenRiC equivalents.
 *
 * Usage:
 *   php bin/missing-views.php
 *   php bin/missing-views.php --verbose
 *   php bin/missing-views.php --package=ahg-accession-manage
 *   php bin/missing-views.php --summary
 */

$heratioBase = '/usr/share/nginx/heratio/packages';
$openricBase = '/usr/share/nginx/OpenRiC/packages';
$verbose     = in_array('--verbose', $argv);
$summaryOnly = in_array('--summary', $argv);
$filterPkg   = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--package=')) {
        $filterPkg = substr($arg, 10);
    }
}

echo "========================================\n";
echo "  OpenRiC — Missing Views Audit\n";
echo "  Heratio: {$heratioBase}\n";
echo "  OpenRiC: {$openricBase}\n";
echo "========================================\n\n";

// ──────────────────────────────────────────────
// Heratio => OpenRiC package mapping
// ──────────────────────────────────────────────
$pluginMap = [
    'ahg-actor-manage'              => 'openric-agent-manage',
    'ahg-information-object-manage' => 'openric-record-manage',
    'ahg-repository-manage'         => 'openric-repository',
    'ahg-accession-manage'          => 'openric-accession',
    'ahg-donor-manage'              => 'openric-donor',
    'ahg-rights-holder-manage'      => 'openric-rights-holder-manage',
    'ahg-storage-manage'            => 'openric-storage-manage',
    'ahg-term-taxonomy'             => 'openric-term-taxonomy',
    'ahg-function-manage'           => 'openric-function-manage',
    'ahg-user-manage'               => 'openric-user-manage',
    'ahg-settings'                  => 'openric-settings-manage',
    'ahg-search'                    => 'openric-search',
    'ahg-static-page'               => 'openric-static-page',
    'ahg-menu-manage'               => 'openric-menu-manage',
    'ahg-reports'                   => 'openric-reports',
    'ahg-cart'                      => 'openric-cart',
    'ahg-favorites'                 => 'openric-favorites',
    'ahg-feedback'                  => 'openric-feedback',
    'ahg-audit-trail'               => 'openric-audit',
    'ahg-backup'                    => 'openric-backup',
    'ahg-data-migration'            => 'openric-data-migration',
    'ahg-display'                   => 'openric-display',
    'ahg-iiif-collection'           => 'openric-digital-object',
    'ahg-request-publish'           => 'openric-request-publish',
    'ahg-workflow'                  => 'openric-workflow',
    'ahg-gallery'                   => 'openric-gallery',
    'ahg-museum'                    => 'openric-museum',
    'ahg-library'                   => 'openric-library',
    'ahg-dam'                       => 'openric-dam',
    'ahg-research'                  => 'openric-research',
    'ahg-dedupe'                    => 'openric-dedupe',
    'ahg-doi-manage'                => 'openric-doi-manage',
    'ahg-loan'                      => 'openric-loan',
    'ahg-preservation'              => 'openric-preservation',
    'ahg-heritage-manage'           => 'openric-heritage',
    'ahg-condition'                 => 'openric-condition',
    'ahg-help'                      => 'openric-help',
    'ahg-acl'                       => 'openric-auth',
    'ahg-core'                      => 'openric-core',
    'ahg-theme-b5'                  => 'openric-theme',
    'ahg-dropdown-manage'           => 'openric-dropdown-manage',
    'ahg-ingest'                    => 'openric-ingest',
    'ahg-custom-fields'             => 'openric-custom-fields',
    'ahg-3d-model'                  => 'openric-3d-model',
    'ahg-access-request'            => 'openric-access-request',
    'ahg-ai-services'               => 'openric-ai-services',
    'ahg-api'                       => 'openric-api',
    'ahg-api-plugin'                => 'openric-api-plugin',
    'ahg-cdpa'                      => 'openric-cdpa',
    'ahg-dacs-manage'               => 'openric-dacs-manage',
    'ahg-dc-manage'                 => 'openric-dc-manage',
    'ahg-discovery'                 => 'openric-discovery',
    'ahg-exhibition'                => 'openric-exhibition',
    'ahg-export'                    => 'openric-export',
    'ahg-extended-rights'           => 'openric-extended-rights',
    'ahg-federation'                => 'openric-federation',
    'ahg-forms'                     => 'openric-forms',
    'ahg-ftp-upload'                => 'openric-ftp-upload',
    'ahg-gis'                       => 'openric-gis',
    'ahg-graphql'                   => 'openric-graphql',
    'ahg-icip'                      => 'openric-icip',
    'ahg-integrity'                 => 'openric-integrity',
    'ahg-ipsas'                     => 'openric-ipsas',
    'ahg-jobs-manage'               => 'openric-jobs-manage',
    'ahg-label'                     => 'openric-label',
    'ahg-landing-page'              => 'openric-landing-page',
    'ahg-media-processing'          => 'openric-media-processing',
    'ahg-media-streaming'           => 'openric-media-streaming',
    'ahg-metadata-export'           => 'openric-metadata-export',
    'ahg-metadata-extraction'       => 'openric-metadata-extraction',
    'ahg-mods-manage'               => 'openric-mods-manage',
    'ahg-multi-tenant'              => 'openric-multi-tenant',
    'ahg-naz'                       => 'openric-naz',
    'ahg-nmmz'                      => 'openric-nmmz',
    'ahg-oai'                       => 'openric-oai',
    'ahg-pdf-tools'                 => 'openric-pdf-tools',
    'ahg-portable-export'           => 'openric-portable-export',
    'ahg-privacy'                   => 'openric-privacy',
    'ahg-provenance'                => 'openric-provenance',
    'ahg-rad-manage'                => 'openric-rad-manage',
    'ahg-registry'                  => 'openric-registry',
    'ahg-researcher-manage'         => 'openric-researcher-manage',
    'ahg-ric'                       => 'openric-ric',
    'ahg-security-clearance'        => 'openric-security-clearance',
    'ahg-semantic-search'           => 'openric-semantic-search',
    'ahg-spectrum'                  => 'openric-spectrum',
    'ahg-statistics'                => 'openric-statistics',
    'ahg-translation'               => 'openric-translation',
    'ahg-vendor'                    => 'openric-vendor',
    'ahg-marketplace'               => 'openric-marketplace',
];

// ──────────────────────────────────────────────
// Normalise view name for comparison
// ──────────────────────────────────────────────
/**
 * Normalises a blade filename for matching:
 *  - lowercase
 *  - kebab-case (underscores to hyphens for non-partial prefixes)
 *  - handle underscore partial prefix (_partial.blade.php)
 *  - strip "Success" suffix (e.g., deleteSuccess => delete)
 */
function checkViewExists(string $heratioViewPath, string $openricViewsDir): array {
    // Get the relative path within the views directory
    // e.g., "show.blade.php", "partials/_row.blade.php", "acl/edit.blade.php"

    $basename = basename($heratioViewPath);
    $relDir   = '';

    // If the view is in a subdirectory, preserve the subdirectory structure
    $viewsPos = strpos($heratioViewPath, '/resources/views/');
    if ($viewsPos !== false) {
        $relFromViews = substr($heratioViewPath, $viewsPos + strlen('/resources/views/'));
        $relDir = dirname($relFromViews);
        if ($relDir === '.') $relDir = '';
    }

    // Generate candidate names
    $stem = str_replace('.blade.php', '', $basename);
    $candidates = [];

    // 1. Exact match
    $candidates[] = buildCandidatePath($relDir, $basename);

    // 2. Lowercase
    $lowerBasename = strtolower($basename);
    $candidates[] = buildCandidatePath($relDir, $lowerBasename);

    // 3. Kebab-case (camelCase => kebab-case)
    $kebab = strtolower(preg_replace('/(?<!^|_)[A-Z]/', '-$0', $stem));
    $candidates[] = buildCandidatePath($relDir, $kebab . '.blade.php');

    // 4. Strip "Success" suffix (e.g., deleteSuccess => delete)
    if (preg_match('/^(.+?)Success$/i', $stem, $sm)) {
        $stripped = strtolower($sm[1]);
        $candidates[] = buildCandidatePath($relDir, $stripped . '.blade.php');
    }

    // 5. Handle underscore partials — if the name starts with _, also check without
    if (str_starts_with($stem, '_')) {
        $noUnderscore = substr($stem, 1);
        $candidates[] = buildCandidatePath($relDir, $noUnderscore . '.blade.php');
        $candidates[] = buildCandidatePath($relDir, strtolower($noUnderscore) . '.blade.php');
        // Also check in a "partials" subdirectory
        $candidates[] = buildCandidatePath('partials', $basename);
        $candidates[] = buildCandidatePath('partials', $lowerBasename);
    }

    // 6. If NOT starting with _, also try _-prefixed version
    if (!str_starts_with($stem, '_')) {
        $candidates[] = buildCandidatePath($relDir, '_' . $basename);
        $candidates[] = buildCandidatePath($relDir, '_' . $lowerBasename);
    }

    // 7. Check in lowercase subdirectory
    if ($relDir !== '') {
        $lowerDir = strtolower($relDir);
        $candidates[] = buildCandidatePath($lowerDir, $basename);
        $candidates[] = buildCandidatePath($lowerDir, $lowerBasename);
    }

    // Deduplicate candidates
    $candidates = array_unique($candidates);

    // Check each candidate
    foreach ($candidates as $candidate) {
        $fullPath = $openricViewsDir . '/' . $candidate;
        if (file_exists($fullPath)) {
            return ['exists' => true, 'path' => $candidate];
        }
    }

    return ['exists' => false, 'path' => buildCandidatePath($relDir, $basename)];
}

function buildCandidatePath(string $dir, string $file): string {
    if ($dir === '' || $dir === '.') return $file;
    return $dir . '/' . $file;
}

// ──────────────────────────────────────────────
// Scan Heratio packages
// ──────────────────────────────────────────────
if (!is_dir($heratioBase)) {
    echo "ERROR: Heratio packages directory not found: {$heratioBase}\n";
    exit(2);
}

$heratioPkgs = array_filter(scandir($heratioBase), function ($d) use ($heratioBase) {
    return $d !== '.' && $d !== '..' && is_dir($heratioBase . '/' . $d);
});
sort($heratioPkgs);

$totalHeratioViews    = 0;
$totalExisting        = 0;
$totalMissing         = 0;
$missingByPackage     = [];  // pkg => [view, ...]
$unmappedPackages     = [];  // Heratio packages with no OpenRiC equivalent
$packageStats         = [];  // pkg => [total, existing, missing]

echo "  Heratio packages: " . count($heratioPkgs) . "\n";
echo "  Mapped packages:  " . count($pluginMap) . "\n\n";

foreach ($heratioPkgs as $heratioPkg) {
    if ($filterPkg !== null && $heratioPkg !== $filterPkg) continue;

    $heratioViewsDir = $heratioBase . '/' . $heratioPkg . '/resources/views';
    if (!is_dir($heratioViewsDir)) continue;

    // Determine OpenRiC equivalent
    $openricPkg = $pluginMap[$heratioPkg] ?? null;

    if ($openricPkg === null) {
        // No mapping — record as unmapped
        // Count views anyway
        $viewCount = 0;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($heratioViewsDir));
        foreach ($rii as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $viewCount++;
            }
        }
        if ($viewCount > 0) {
            $unmappedPackages[$heratioPkg] = $viewCount;
        }
        continue;
    }

    $openricViewsDir = $openricBase . '/' . $openricPkg . '/resources/views';
    $openricPkgExists = is_dir($openricViewsDir);

    // Collect all Heratio blade files
    $heratioViews = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($heratioViewsDir));
    foreach ($rii as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $heratioViews[] = $file->getPathname();
        }
    }
    sort($heratioViews);

    if (count($heratioViews) === 0) continue;

    $pkgTotal    = count($heratioViews);
    $pkgExisting = 0;
    $pkgMissing  = 0;
    $pkgMissingList = [];

    foreach ($heratioViews as $heratioViewPath) {
        $totalHeratioViews++;

        if (!$openricPkgExists) {
            // Entire OpenRiC package missing
            $totalMissing++;
            $pkgMissing++;
            $relView = str_replace($heratioViewsDir . '/', '', $heratioViewPath);
            $pkgMissingList[] = $relView;
            continue;
        }

        $result = checkViewExists($heratioViewPath, $openricViewsDir);

        if ($result['exists']) {
            $totalExisting++;
            $pkgExisting++;
        } else {
            $totalMissing++;
            $pkgMissing++;
            $pkgMissingList[] = $result['path'];
        }
    }

    $packageStats[$heratioPkg] = [
        'openricPkg' => $openricPkg,
        'total'      => $pkgTotal,
        'existing'   => $pkgExisting,
        'missing'    => $pkgMissing,
    ];

    if (count($pkgMissingList) > 0) {
        $missingByPackage[$heratioPkg] = $pkgMissingList;
    }
}

// ──────────────────────────────────────────────
// Summary
// ──────────────────────────────────────────────
$pctComplete = $totalHeratioViews > 0
    ? round($totalExisting / $totalHeratioViews * 100, 1)
    : 0;

echo "╔══════════════════════════════════════════════════╗\n";
echo "║           MISSING VIEWS AUDIT                   ║\n";
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  Total Heratio views    %'.-20d   ║\n", $totalHeratioViews);
printf("║  Existing in OpenRiC    %'.-20d   ║\n", $totalExisting);
printf("║  MISSING from OpenRiC   %'.-20d   ║\n", $totalMissing);
printf("║  Completeness           %'.-18s%%  ║\n", $pctComplete);
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  Unmapped Heratio pkgs  %'.-20d   ║\n", count($unmappedPackages));
echo "╚══════════════════════════════════════════════════╝\n";

// ──────────────────────────────────────────────
// Package-level summary table
// ──────────────────────────────────────────────
echo "\n=== PACKAGE SUMMARY ===\n\n";
printf("  %-35s %-30s %5s %5s %5s %6s\n", 'Heratio Package', 'OpenRiC Package', 'Total', 'Have', 'Miss', '%');
echo "  " . str_repeat('-', 112) . "\n";

// Sort by missing count descending
uasort($packageStats, fn($a, $b) => $b['missing'] <=> $a['missing']);

foreach ($packageStats as $heratioPkg => $stats) {
    $pct = $stats['total'] > 0
        ? round($stats['existing'] / $stats['total'] * 100, 0)
        : 100;

    $marker = $stats['missing'] > 0 ? '*' : ' ';

    printf("  %-35s %-30s %5d %5d %5d %5d%%\n",
        $heratioPkg,
        $stats['openricPkg'],
        $stats['total'],
        $stats['existing'],
        $stats['missing'],
        $pct
    );
}
echo "\n";

// ──────────────────────────────────────────────
// Missing views by package (detail)
// ──────────────────────────────────────────────
if (!$summaryOnly && count($missingByPackage) > 0) {
    echo "=== MISSING VIEWS BY PACKAGE ===\n\n";

    ksort($missingByPackage);

    foreach ($missingByPackage as $heratioPkg => $views) {
        $openricPkg = $pluginMap[$heratioPkg] ?? '?';
        $stats = $packageStats[$heratioPkg] ?? ['total' => 0, 'existing' => 0, 'missing' => 0];

        echo "  {$heratioPkg} => {$openricPkg}";
        echo "  ({$stats['missing']} missing of {$stats['total']})\n";

        sort($views);
        foreach ($views as $view) {
            echo "    MISSING  {$view}\n";
        }
        echo "\n";
    }
}

// ──────────────────────────────────────────────
// Unmapped packages
// ──────────────────────────────────────────────
if (count($unmappedPackages) > 0) {
    echo "=== HERATIO PACKAGES WITHOUT OPENRIC EQUIVALENT ===\n\n";

    arsort($unmappedPackages);

    foreach ($unmappedPackages as $pkg => $viewCount) {
        printf("  %-40s %d views\n", $pkg, $viewCount);
    }
    echo "\n";
}

// ──────────────────────────────────────────────
// Verbose: existing views
// ──────────────────────────────────────────────
if ($verbose) {
    echo "=== EXISTING (MATCHED) VIEW COUNTS ===\n";
    foreach ($packageStats as $heratioPkg => $stats) {
        if ($stats['existing'] > 0) {
            echo "  {$heratioPkg}: {$stats['existing']}/{$stats['total']}\n";
        }
    }
    echo "\n";
}

echo "Done.\n";

// Exit code: 1 if missing views found
exit($totalMissing > 0 ? 1 : 0);
