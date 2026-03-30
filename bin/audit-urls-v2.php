#!/usr/bin/env php
<?php
/**
 * OpenRiC — Audit URLs v2: Page-by-Page Comparison with Heratio
 *
 * For every show/edit/browse/delete blade in OpenRiC:
 *   1. Extract every <a href> with link text
 *   2. Find the matching Heratio template
 *   3. Extract every <a href> with link text from Heratio
 *   4. Pair by link text
 *   5. Flag SCOPE_MISMATCH where Heratio is scoped but OpenRiC is generic
 *   6. Flag MISSING_IN_OPENRIC where Heratio has a link OpenRiC doesn't
 *
 * Resolves route() and url() calls.  Also resolves @include directives
 * to inline partial content for analysis.
 *
 * Usage:
 *   php bin/audit-urls-v2.php
 *   php bin/audit-urls-v2.php --verbose
 *   php bin/audit-urls-v2.php --package=openric-accession
 */

$openricBase = '/usr/share/nginx/OpenRiC/packages';
$heratioBase = '/usr/share/nginx/heratio/packages';
$verbose     = in_array('--verbose', $argv);
$filterPkg   = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--package=')) {
        $filterPkg = substr($arg, 10);
    }
}

echo "========================================\n";
echo "  OpenRiC — Audit URLs v2\n";
echo "  Page-by-page link comparison\n";
echo "  OpenRiC: {$openricBase}\n";
echo "  Heratio: {$heratioBase}\n";
echo "========================================\n\n";

// ──────────────────────────────────────────────
// Package mapping: OpenRiC => Heratio
// ──────────────────────────────────────────────
$mapping = [
    'openric-agent-manage'       => ['ahg-actor-manage'],
    'openric-record-manage'      => ['ahg-information-object-manage'],
    'openric-repository'         => ['ahg-repository-manage'],
    'openric-accession'          => ['ahg-accession-manage'],
    'openric-donor'              => ['ahg-donor-manage'],
    'openric-rights-holder-manage' => ['ahg-rights-holder-manage'],
    'openric-storage-manage'     => ['ahg-storage-manage'],
    'openric-term-taxonomy'      => ['ahg-term-taxonomy'],
    'openric-function-manage'    => ['ahg-function-manage'],
    'openric-user-manage'        => ['ahg-user-manage'],
    'openric-settings-manage'    => ['ahg-settings'],
    'openric-search'             => ['ahg-search'],
    'openric-static-page'        => ['ahg-static-page'],
    'openric-menu-manage'        => ['ahg-menu-manage'],
    'openric-reports'            => ['ahg-reports'],
    'openric-cart'               => ['ahg-cart'],
    'openric-favorites'          => ['ahg-favorites'],
    'openric-feedback'           => ['ahg-feedback'],
    'openric-audit'              => ['ahg-audit-trail'],
    'openric-backup'             => ['ahg-backup'],
    'openric-data-migration'     => ['ahg-data-migration'],
    'openric-display'            => ['ahg-display'],
    'openric-digital-object'     => ['ahg-iiif-collection'],
    'openric-request-publish'    => ['ahg-request-publish'],
    'openric-workflow'           => ['ahg-workflow'],
    'openric-gallery'            => ['ahg-gallery'],
    'openric-museum'             => ['ahg-museum'],
    'openric-library'            => ['ahg-library'],
    'openric-dam'                => ['ahg-dam'],
    'openric-research'           => ['ahg-research'],
    'openric-dedupe'             => ['ahg-dedupe'],
    'openric-doi-manage'         => ['ahg-doi-manage'],
    'openric-loan'               => ['ahg-loan'],
    'openric-preservation'       => ['ahg-preservation'],
    'openric-heritage'           => ['ahg-heritage-manage'],
    'openric-condition'          => ['ahg-condition'],
    'openric-help'               => ['ahg-help'],
    'openric-auth'               => ['ahg-acl'],
    'openric-core'               => ['ahg-core'],
    'openric-theme'              => ['ahg-theme-b5'],
    'openric-dropdown-manage'    => ['ahg-dropdown-manage'],
    'openric-ingest'             => ['ahg-ingest'],
    'openric-custom-fields'      => ['ahg-custom-fields'],
];

// Page types to audit
$pagePatterns = ['show', 'edit', 'browse', 'delete', 'view', 'add', 'detail', 'index'];

// ──────────────────────────────────────────────
// Helper: resolve @include directives in blade content
// ──────────────────────────────────────────────
function resolveIncludes(string $content, string $viewsDir, int $depth = 0): string {
    if ($depth > 5) return $content;  // prevent infinite recursion

    return preg_replace_callback(
        "/@include\s*\(\s*['\"]([^'\"]+)['\"]/",
        function ($m) use ($viewsDir, $depth) {
            $viewName = $m[1];

            // Convert dot notation to path
            // e.g., 'accession::partials._row' => partials/_row.blade.php
            // Strip package prefix if present
            if (str_contains($viewName, '::')) {
                $viewName = explode('::', $viewName, 2)[1];
            }

            $viewPath = str_replace('.', '/', $viewName);

            // Try direct path
            $candidates = [
                $viewsDir . '/' . $viewPath . '.blade.php',
                $viewsDir . '/' . '_' . basename($viewPath) . '.blade.php',
                $viewsDir . '/' . dirname($viewPath) . '/_' . basename($viewPath) . '.blade.php',
            ];

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    $included = file_get_contents($candidate);
                    return resolveIncludes($included, $viewsDir, $depth + 1);
                }
            }

            // Could not resolve; return original directive as-is
            return $m[0];
        },
        $content
    );
}

// ──────────────────────────────────────────────
// Helper: extract links from blade content
// Returns: [['text' => '...', 'href' => '...', 'scoped' => bool], ...]
// ──────────────────────────────────────────────
function extractLinks(string $content): array {
    $links = [];

    // Flatten whitespace for regex
    $flat = preg_replace('/\s+/', ' ', $content);

    if (!preg_match_all('/<a\s([^>]*?)>(.*?)<\/a>/si', $flat, $matches, PREG_SET_ORDER)) {
        return $links;
    }

    foreach ($matches as $m) {
        $attrs   = $m[1];
        $rawText = $m[2];

        // Extract href — handle Blade expressions {{ ... }} inside quotes
        if (!preg_match('/href\s*=\s*"((?:[^"]*?\{\{.*?\}\}[^"]*?)*[^"]*?)"/i', $attrs, $hm)) {
            if (!preg_match('/href\s*=\s*["\']([^"\']*?)["\']/i', $attrs, $hm)) continue;
        }

        $href = $hm[1];

        // Clean text
        $text = trim(strip_tags($rawText));
        $text = preg_replace('/\s+/', ' ', $text);

        // Skip empty, anchors, javascript
        if ($text === '' && $href === '#') continue;
        if (str_starts_with($href, 'javascript:')) continue;

        $scoped = isHrefScoped($href);

        $links[] = [
            'text'   => $text,
            'href'   => $href,
            'scoped' => $scoped,
        ];
    }

    return $links;
}

// ──────────────────────────────────────────────
// Helper: determine if href is scoped (references a record identifier)
// ──────────────────────────────────────────────
function isHrefScoped(string $href): bool {
    // Contains a PHP variable reference
    if (preg_match('/\$[a-zA-Z_]/', $href)) return true;

    // Blade expression with variable
    if (str_contains($href, '{{ $') || str_contains($href, '{!! $')) return true;

    // route() with a second parameter
    if (preg_match("/route\(\s*['\"][^'\"]+['\"]\s*,/", $href)) return true;

    // url() with concatenation or variable
    if (preg_match("/url\([^)]*\\$/", $href)) return true;

    // Direct ->slug, ->id access
    if (str_contains($href, '->slug') || str_contains($href, '->id')) return true;
    if (str_contains($href, '->getId()') || str_contains($href, '->getSlug()')) return true;

    return false;
}

// ──────────────────────────────────────────────
// Find matching Heratio blade for an OpenRiC blade
// ──────────────────────────────────────────────
function findHeratioMatch(string $openricPkg, string $bladeFilename, array $mapping, string $heratioBase): ?string {
    if (!isset($mapping[$openricPkg])) return null;

    foreach ($mapping[$openricPkg] as $heratioPkg) {
        $heratioDir = $heratioBase . '/' . $heratioPkg . '/resources/views';
        if (!is_dir($heratioDir)) continue;

        // Direct match
        $candidate = $heratioDir . '/' . $bladeFilename;
        if (file_exists($candidate)) return $candidate;

        // Check in subdirectories
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($heratioDir));
        foreach ($rii as $file) {
            if ($file->isFile() && $file->getFilename() === $bladeFilename) {
                return $file->getPathname();
            }
        }
    }

    return null;
}

// ──────────────────────────────────────────────
// Collect OpenRiC blade files to audit
// ──────────────────────────────────────────────
$filesToAudit = [];

foreach ($mapping as $openricPkg => $heratioPkgs) {
    if ($filterPkg !== null && $openricPkg !== $filterPkg) continue;

    $viewsDir = $openricBase . '/' . $openricPkg . '/resources/views';
    if (!is_dir($viewsDir)) continue;

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
    foreach ($rii as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) continue;

        $basename = $file->getFilename();
        $stem = str_replace('.blade.php', '', $basename);

        // Only audit page types we care about
        $isTarget = false;
        foreach ($pagePatterns as $pat) {
            if ($stem === $pat || str_ends_with($stem, '-' . $pat) || str_starts_with($stem, $pat . '-')) {
                $isTarget = true;
                break;
            }
        }
        if (!$isTarget) continue;

        $filesToAudit[] = [
            'path'      => $file->getPathname(),
            'basename'  => $basename,
            'package'   => $openricPkg,
            'viewsDir'  => $viewsDir,
        ];
    }
}

echo "  Pages to audit: " . count($filesToAudit) . "\n";
echo "  Packages mapped: " . count($mapping) . "\n\n";

// ──────────────────────────────────────────────
// Audit each page
// ──────────────────────────────────────────────
$totalPages        = 0;
$totalMatched      = 0;
$totalNoMatch      = 0;
$totalScopeMismatch = 0;
$totalMissingLinks  = 0;
$issues             = [];  // [type, openricFile, text, openricHref, heratioHref, detail]

foreach ($filesToAudit as $entry) {
    $totalPages++;

    $openricPath  = $entry['path'];
    $openricPkg   = $entry['package'];
    $bladeName    = $entry['basename'];
    $openricViews = $entry['viewsDir'];
    $relPath      = $openricPkg . '/resources/views/' . $bladeName;

    // Find Heratio equivalent
    $heratioPath = findHeratioMatch($openricPkg, $bladeName, $mapping, $heratioBase);

    if ($heratioPath === null) {
        $totalNoMatch++;
        if ($verbose) {
            echo "  SKIP  {$relPath} (no Heratio match)\n";
        }
        continue;
    }

    $totalMatched++;

    // Determine Heratio views dir for @include resolution
    $heratioViewsDir = dirname($heratioPath);
    // Walk up to the views directory
    if (basename($heratioViewsDir) !== 'views') {
        $heratioViewsDir = dirname($heratioPath);
        while ($heratioViewsDir !== '/' && basename($heratioViewsDir) !== 'views') {
            $heratioViewsDir = dirname($heratioViewsDir);
        }
    }

    // Read and resolve includes
    $openricContent = file_get_contents($openricPath);
    $openricContent = resolveIncludes($openricContent, $openricViews);

    $heratioContent = file_get_contents($heratioPath);
    $heratioContent = resolveIncludes($heratioContent, $heratioViewsDir);

    // Extract links
    $openricLinks = extractLinks($openricContent);
    $heratioLinks = extractLinks($heratioContent);

    // Index OpenRiC links by normalised text
    $openricByText = [];
    foreach ($openricLinks as $link) {
        $key = mb_strtolower(trim($link['text']));
        if ($key === '') continue;
        $openricByText[$key] = $link;
    }

    // Index Heratio links by normalised text
    $heratioByText = [];
    foreach ($heratioLinks as $link) {
        $key = mb_strtolower(trim($link['text']));
        if ($key === '') continue;
        $heratioByText[$key] = $link;
    }

    // Compare
    foreach ($heratioByText as $textKey => $hLink) {
        // Skip external/anchor links
        if (str_starts_with($hLink['href'], 'http://') || str_starts_with($hLink['href'], 'https://')) continue;
        if ($hLink['href'] === '#' || str_starts_with($hLink['href'], '#')) continue;

        if (!isset($openricByText[$textKey])) {
            // MISSING_IN_OPENRIC
            $totalMissingLinks++;
            $issues[] = [
                'type'        => 'MISSING_IN_OPENRIC',
                'file'        => $relPath,
                'heratioFile' => str_replace($heratioBase . '/', '', $heratioPath),
                'text'        => $hLink['text'],
                'openricHref' => '',
                'heratioHref' => $hLink['href'],
                'detail'      => "Link \"{$hLink['text']}\" exists in Heratio but not in OpenRiC",
            ];
            continue;
        }

        $oLink = $openricByText[$textKey];

        // SCOPE_MISMATCH: Heratio is scoped, OpenRiC is not
        if ($hLink['scoped'] && !$oLink['scoped']) {
            $totalScopeMismatch++;
            $issues[] = [
                'type'        => 'SCOPE_MISMATCH',
                'file'        => $relPath,
                'heratioFile' => str_replace($heratioBase . '/', '', $heratioPath),
                'text'        => $oLink['text'],
                'openricHref' => $oLink['href'],
                'heratioHref' => $hLink['href'],
                'detail'      => "Heratio href is scoped but OpenRiC is generic",
            ];
        }
    }
}

// ──────────────────────────────────────────────
// Summary
// ──────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════╗\n";
echo "║         URL COMPARISON AUDIT (v2)               ║\n";
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  Pages audited          %'.-20d   ║\n", $totalPages);
printf("║  Heratio matches found  %'.-20d   ║\n", $totalMatched);
printf("║  No Heratio match       %'.-20d   ║\n", $totalNoMatch);
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  SCOPE_MISMATCH         %'.-20d   ║\n", $totalScopeMismatch);
printf("║  MISSING_IN_OPENRIC     %'.-20d   ║\n", $totalMissingLinks);
printf("║  Total issues           %'.-20d   ║\n", count($issues));
echo "╚══════════════════════════════════════════════════╝\n";

// ──────────────────────────────────────────────
// Detail: SCOPE_MISMATCH
// ──────────────────────────────────────────────
$scopeIssues   = array_filter($issues, fn($i) => $i['type'] === 'SCOPE_MISMATCH');
$missingIssues = array_filter($issues, fn($i) => $i['type'] === 'MISSING_IN_OPENRIC');

if (count($scopeIssues) > 0) {
    echo "\n=== SCOPE_MISMATCH (" . count($scopeIssues) . ") ===\n";
    echo "  Heratio has a scoped link but OpenRiC's equivalent is generic.\n\n";

    // Group by file
    $byFile = [];
    foreach ($scopeIssues as $issue) {
        $byFile[$issue['file']][] = $issue;
    }
    ksort($byFile);

    foreach ($byFile as $file => $items) {
        echo "  {$file}\n";
        foreach ($items as $issue) {
            echo "    SCOPE_MISMATCH  \"{$issue['text']}\"\n";
            echo "      OpenRiC: {$issue['openricHref']}\n";
            echo "      Heratio: {$issue['heratioHref']}\n";
        }
        echo "\n";
    }
}

// ──────────────────────────────────────────────
// Detail: MISSING_IN_OPENRIC
// ──────────────────────────────────────────────
if (count($missingIssues) > 0) {
    echo "\n=== MISSING_IN_OPENRIC (" . count($missingIssues) . ") ===\n";
    echo "  Links present in Heratio but absent from corresponding OpenRiC page.\n\n";

    // Group by file
    $byFile = [];
    foreach ($missingIssues as $issue) {
        $byFile[$issue['file']][] = $issue;
    }
    ksort($byFile);

    foreach ($byFile as $file => $items) {
        echo "  {$file}\n";
        echo "    (Heratio: {$items[0]['heratioFile']})\n";
        foreach ($items as $issue) {
            echo "    MISSING  \"{$issue['text']}\"  href=\"{$issue['heratioHref']}\"\n";
        }
        echo "\n";
    }
}

// ──────────────────────────────────────────────
// Verbose: matched pages with no issues
// ──────────────────────────────────────────────
if ($verbose && count($issues) === 0) {
    echo "\n  All matched pages have equivalent links. No issues found.\n";
}

if (count($issues) === 0) {
    echo "\n  No issues found.\n";
}

echo "\n";

// Exit code: 1 if issues found
exit(count($issues) > 0 ? 1 : 0);
