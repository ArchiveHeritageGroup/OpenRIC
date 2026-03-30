#!/usr/bin/env php
<?php
/**
 * Full Control Audit: OpenRiC vs Heratio
 *
 * Reads every OpenRiC blade view and its mapped Heratio blade view.
 * Counts every UI element (buttons, links, inputs, selects, textareas,
 * checkboxes, radios, headings, badges, tables, labels, icons, images, forms).
 * Computes delta per package.
 *
 * Usage:
 *   php bin/audit-controls.php [--filter PACKAGE] [--json] [--help]
 *
 * Options:
 *   --filter PACKAGE  Only audit matching package(s)
 *   --json            Output JSON instead of text
 *   --help            Show usage
 */

$openricBase = '/usr/share/nginx/OpenRiC/packages';
$heratioBase = '/usr/share/nginx/heratio/packages';

// Parse CLI arguments
$filterPkg = '';
$jsonOutput = false;

$args = array_slice($argv, 1);
for ($i = 0; $i < count($args); $i++) {
    switch ($args[$i]) {
        case '--filter':
            $filterPkg = $args[++$i] ?? '';
            break;
        case '--json':
            $jsonOutput = true;
            break;
        case '--help':
            echo <<<HELP
OpenRiC Control Audit — OpenRiC vs Heratio Blade View Comparison

Usage: php bin/audit-controls.php [OPTIONS]

Options:
  --filter PACKAGE   Only audit matching package(s)
  --json             Output JSON instead of terminal text
  --help             Show this help

HELP;
            exit(0);
    }
}

// ── Package mapping: OpenRiC → Heratio ──────────────────────────────────────

$mapping = [
    'openric-agent-manage' => ['ahg-actor-manage'],
    'openric-record-manage' => ['ahg-information-object-manage'],
    'openric-repository' => ['ahg-repository-manage'],
    'openric-accession' => ['ahg-accession-manage'],
    'openric-donor' => ['ahg-donor-manage'],
    'openric-rights-holder-manage' => ['ahg-rights-holder-manage'],
    'openric-storage-manage' => ['ahg-storage-manage'],
    'openric-term-taxonomy' => ['ahg-term-taxonomy'],
    'openric-function-manage' => ['ahg-function-manage'],
    'openric-user-manage' => ['ahg-user-manage'],
    'openric-settings-manage' => ['ahg-settings'],
    'openric-search' => ['ahg-search'],
    'openric-static-page' => ['ahg-static-page'],
    'openric-menu-manage' => ['ahg-menu-manage'],
    'openric-reports' => ['ahg-reports'],
    'openric-cart' => ['ahg-cart'],
    'openric-favorites' => ['ahg-favorites'],
    'openric-feedback' => ['ahg-feedback'],
    'openric-audit' => ['ahg-audit-trail'],
    'openric-backup' => ['ahg-backup'],
    'openric-data-migration' => ['ahg-data-migration'],
    'openric-display' => ['ahg-display'],
    'openric-digital-object' => ['ahg-iiif-collection'],
    'openric-request-publish' => ['ahg-request-publish'],
    'openric-workflow' => ['ahg-workflow'],
    'openric-gallery' => ['ahg-gallery'],
    'openric-museum' => ['ahg-museum'],
    'openric-library' => ['ahg-library'],
    'openric-dam' => ['ahg-dam'],
    'openric-research' => ['ahg-research'],
    'openric-researcher-manage' => ['ahg-researcher-manage'],
    'openric-dedupe' => ['ahg-dedupe'],
    'openric-doi-manage' => ['ahg-doi-manage'],
    'openric-loan' => ['ahg-loan'],
    'openric-preservation' => ['ahg-preservation'],
    'openric-ai' => ['ahg-ai-services'],
    'openric-3d-model' => ['ahg-3d-model'],
    'openric-ftp-upload' => ['ahg-ftp-upload'],
    'openric-help' => ['ahg-help'],
    'openric-integrity' => ['ahg-integrity'],
    'openric-metadata-extraction' => ['ahg-metadata-extraction'],
    'openric-portable-export' => ['ahg-portable-export'],
    'openric-ric' => ['ahg-ric'],
    'openric-auth' => ['ahg-acl'],
    'openric-heritage' => ['ahg-heritage-manage'],
    'openric-condition' => ['ahg-condition'],
    'openric-dropdown-manage' => ['ahg-dropdown-manage'],
    'openric-ingest' => ['ahg-ingest'],
    'openric-export' => ['ahg-export'],
    'openric-jobs-manage' => ['ahg-jobs-manage'],
    'openric-media-processing' => ['ahg-media-processing'],
    'openric-pdf-tools' => ['ahg-pdf-tools'],
    'openric-theme' => ['ahg-theme-b5'],
    'openric-core' => ['ahg-core'],
    'openric-api' => ['ahg-api-plugin'],
    'openric-access-request' => ['ahg-access-request'],
    'openric-privacy' => ['ahg-privacy'],
    'openric-spectrum' => ['ahg-spectrum'],
    'openric-icip' => ['ahg-icip'],
    'openric-naz' => ['ahg-naz'],
    'openric-nmmz' => ['ahg-nmmz'],
    'openric-exhibition' => ['ahg-exhibition'],
    'openric-ipsas' => ['ahg-ipsas'],
    'openric-semantic-search' => ['ahg-semantic-search'],
    'openric-statistics' => ['ahg-statistics'],
    'openric-multi-tenant' => ['ahg-multi-tenant'],
    'openric-landing-page' => ['ahg-landing-page'],
    'openric-forms' => ['ahg-forms'],
    'openric-label' => ['ahg-label'],
    'openric-gis' => ['ahg-gis'],
    'openric-translation' => ['ahg-translation'],
    'openric-graphql' => ['ahg-graphql'],
    'openric-discovery' => ['ahg-discovery'],
    'openric-vendor' => ['ahg-vendor'],
    'openric-cdpa' => ['ahg-cdpa'],
    'openric-registry' => ['ahg-registry'],
    'openric-marketplace' => ['ahg-marketplace'],
    'openric-custom-fields' => ['ahg-custom-fields'],
    'openric-metadata-export' => ['ahg-metadata-export'],
    'openric-provenance' => ['ahg-provenance'],
    'openric-federation' => ['ahg-federation'],
    'openric-ai-governance' => [],
    'openric-triplestore' => [],
    'openric-graph' => [],
];

// ── Helpers ──────────────────────────────────────────────────────────────

/**
 * Count all UI controls in a blade/HTML file.
 */
function countControls(string $content): array
{
    $c = [
        'buttons'      => 0,
        'links'        => 0,
        'inputs'       => 0,
        'selects'      => 0,
        'textareas'    => 0,
        'checkboxes'   => 0,
        'radios'       => 0,
        'headings'     => 0,
        'badges'       => 0,
        'tables'       => 0,
        'table_cols'   => 0,
        'labels'       => 0,
        'icons'        => 0,
        'images'       => 0,
        'forms'        => 0,
        'btn_classes'  => [],
        'link_hrefs'   => [],
        'badge_types'  => [],
        'field_badges' => ['required' => 0, 'recommended' => 0, 'optional' => 0],
    ];

    // Buttons: <button, type="submit", type="button", .btn classes
    $c['buttons'] = preg_match_all('/<button\b/i', $content)
                  + preg_match_all('/\btype=["\']submit["\']/i', $content)
                  - preg_match_all('/<button[^>]*type=["\']submit["\']/i', $content); // avoid double-count

    // Links: <a href=
    $c['links'] = preg_match_all('/<a\s[^>]*href\s*=/i', $content);

    // Extract link hrefs
    if (preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']*?)["\']/i', $content, $m)) {
        foreach ($m[1] as $href) {
            $href = trim($href);
            if ($href && $href !== '#') {
                $c['link_hrefs'][] = $href;
            }
        }
    }

    // Extract button classes
    if (preg_match_all('/<(?:button|a)\s[^>]*class\s*=\s*["\']([^"\']*btn[^"\']*?)["\']/i', $content, $m)) {
        foreach ($m[1] as $cls) {
            if (preg_match_all('/(?<![a-z-])(btn[-\w]*)\b/', $cls, $bm)) {
                foreach ($bm[1] as $b) $c['btn_classes'][] = $b;
            }
        }
    }

    // Form fields (excluding hidden/checkbox/radio/submit/button types for inputs)
    $c['inputs'] = preg_match_all('/<input\b(?![^>]*type=["\'](?:checkbox|radio|hidden|submit|button)["\'])/i', $content);
    $c['selects'] = preg_match_all('/<select\b/i', $content);
    $c['textareas'] = preg_match_all('/<textarea\b/i', $content);
    $c['checkboxes'] = preg_match_all('/<input[^>]*type=["\']checkbox["\']/i', $content);
    $c['radios'] = preg_match_all('/<input[^>]*type=["\']radio["\']/i', $content);

    // Headings
    $c['headings'] = preg_match_all('/<h[1-6]\b/i', $content);

    // Badges
    $c['badges'] = preg_match_all('/\bbadge\b/i', $content);
    if (preg_match_all('/badge\s+(bg-\w+)/i', $content, $bm)) {
        $c['badge_types'] = array_count_values($bm[1]);
    }

    // Field badges: Required/Recommended/Optional
    $c['field_badges']['required'] = preg_match_all('/Required<\/span>/i', $content);
    $c['field_badges']['recommended'] = preg_match_all('/Recommended<\/span>/i', $content);
    $c['field_badges']['optional'] = preg_match_all('/Optional<\/span>/i', $content);

    // Tables
    $c['tables'] = preg_match_all('/<table\b/i', $content);
    $c['table_cols'] = preg_match_all('/<th\b/i', $content);

    // Labels
    $c['labels'] = preg_match_all('/<label\b/i', $content);

    // Icons (FontAwesome, Bootstrap Icons)
    $c['icons'] = preg_match_all('/<i\s+class\s*=\s*["\'][^"\']*\b(?:fa[srlb]?|bi)\s/i', $content);

    // Images
    $c['images'] = preg_match_all('/<img\b/i', $content);

    // Forms
    $c['forms'] = preg_match_all('/<form\b/i', $content);

    // Total controls
    $c['total_fields'] = $c['inputs'] + $c['selects'] + $c['textareas'] + $c['checkboxes'] + $c['radios'];
    $c['total_controls'] = $c['buttons'] + $c['links'] + $c['total_fields'] + $c['headings'] + $c['labels'] + $c['badges'] + $c['icons'];

    // Detect layout
    $c['layout'] = 'unknown';
    if (preg_match("/extends\(['\"]theme::layouts\.([\w]+)['\"]\)/", $content, $lm)) {
        $c['layout'] = $lm[1];
    }

    // Detect sidebar
    $c['sidebar'] = 'none';
    if (preg_match('/col-md-3.*col-md-9/s', $content)) $c['sidebar'] = 'left';
    if (preg_match('/col-md-9.*col-md-3/s', $content)) $c['sidebar'] = 'right';
    if (preg_match('/sidebar|context-menu|_contextMenu/i', $content)) {
        if ($c['sidebar'] === 'none') $c['sidebar'] = 'detected';
    }

    $c['btn_classes'] = array_count_values($c['btn_classes']);

    return $c;
}

/**
 * Find the Heratio equivalent blade views for a given OpenRiC blade view.
 *
 * Searches the mapped Heratio package's resources/views/ directory for blade
 * files with matching names (with name normalization for browse/show/edit/etc).
 */
function findHeratioEquivalent(string $openricPkg, string $viewName): array
{
    global $heratioBase, $mapping;

    if (!isset($mapping[$openricPkg])) {
        return [];
    }

    $heratioPkgs = $mapping[$openricPkg];
    if (empty($heratioPkgs)) {
        return [];
    }

    // View name mapping: OpenRiC blade basename -> Heratio blade basenames
    $viewMap = [
        'browse'  => ['browse', 'list', 'index'],
        'show'    => ['show', 'index', 'view'],
        'edit'    => ['edit', 'update'],
        'delete'  => ['delete'],
        'create'  => ['create', 'add', 'new'],
        'add'     => ['add', 'create', 'new'],
        'new'     => ['new', 'create', 'add'],
        'index'   => ['index', 'browse', 'list'],
        'list'    => ['list', 'browse', 'index'],
        'view'    => ['view', 'show', 'index'],
        'print'   => ['print', 'printPreview'],
    ];

    $viewBasename = pathinfo($viewName, PATHINFO_FILENAME);
    $viewBasename = str_replace('.blade', '', $viewBasename);
    $isPartial = (strpos($viewBasename, '_') === 0);

    $results = [];

    foreach ($heratioPkgs as $heratioPkg) {
        $heratioViewDir = "$heratioBase/$heratioPkg/resources/views";
        if (!is_dir($heratioViewDir)) {
            continue;
        }

        // Build candidate names list
        $candidates = [];

        // Try view name mapping (normalized names)
        if (isset($viewMap[$viewBasename])) {
            foreach ($viewMap[$viewBasename] as $candidate) {
                $candidates[] = $candidate;
            }
        }

        // Always try the exact name
        $candidates[] = $viewBasename;

        // For partials, try both with and without underscore prefix
        if ($isPartial) {
            $withoutPrefix = ltrim($viewBasename, '_');
            $candidates[] = $withoutPrefix;
            $candidates[] = '_' . $withoutPrefix;
        } else {
            $candidates[] = '_' . $viewBasename;
        }

        $candidates = array_unique($candidates);

        // Search for matching files (including subdirectories)
        foreach ($candidates as $candidate) {
            // Direct match in views root
            $file = "$heratioViewDir/$candidate.blade.php";
            if (file_exists($file) && !in_array($file, $results)) {
                $results[] = $file;
            }

            // Search subdirectories
            if (is_dir($heratioViewDir)) {
                $subdirs = glob("$heratioViewDir/*/", GLOB_ONLYDIR);
                foreach ($subdirs as $subdir) {
                    $file = "$subdir$candidate.blade.php";
                    if (file_exists($file) && !in_array($file, $results)) {
                        $results[] = $file;
                    }
                }
            }
        }
    }

    return $results;
}

// ── Main scan ────────────────────────────────────────────────────────────

$allResults = [];
$totals = [
    'openric_controls' => 0,
    'heratio_controls' => 0,
    'delta' => 0,
    'files' => 0,
    'missing_heratio' => 0,
];

// Find all OpenRiC blade files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($openricBase, RecursiveDirectoryIterator::SKIP_DOTS)
);

$bladeFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && preg_match('/\.blade\.php$/', $file->getFilename())) {
        $bladeFiles[] = $file->getPathname();
    }
}
sort($bladeFiles);

foreach ($bladeFiles as $bladePath) {
    // Extract package name
    $rel = str_replace($openricBase . '/', '', $bladePath);
    $parts = explode('/', $rel);
    $package = $parts[0];
    $viewFile = basename($bladePath);

    // Apply filter
    if ($filterPkg && stripos($package, $filterPkg) === false) {
        continue;
    }

    $openricContent = file_get_contents($bladePath);
    $oControls = countControls($openricContent);

    // Find Heratio equivalent
    $heratioFiles = findHeratioEquivalent($package, $viewFile);
    $hControls = null;
    $heratioPath = '';

    if (!empty($heratioFiles)) {
        // Merge controls from all matching Heratio files
        $heratioContent = '';
        foreach ($heratioFiles as $hf) {
            $heratioContent .= file_get_contents($hf) . "\n";
        }
        $hControls = countControls($heratioContent);
        $heratioPath = implode(', ', array_map(function ($f) use ($heratioBase) {
            return str_replace($heratioBase . '/', '', $f);
        }, $heratioFiles));
    }

    $row = [
        'package'       => $package,
        'view'          => str_replace('.blade.php', '', $viewFile),
        'openric'       => $bladePath,
        'heratio_files' => $heratioPath,
        'layout'        => $oControls['layout'],
        'sidebar'       => $oControls['sidebar'],
        'o_buttons'     => $oControls['buttons'],
        'o_links'       => $oControls['links'],
        'o_fields'      => $oControls['total_fields'],
        'o_headings'    => $oControls['headings'],
        'o_labels'      => $oControls['labels'],
        'o_badges'      => $oControls['badges'],
        'o_icons'       => $oControls['icons'],
        'o_tables'      => $oControls['tables'],
        'o_th'          => $oControls['table_cols'],
        'o_total'       => $oControls['total_controls'],
        'o_btn_cls'     => $oControls['btn_classes'],
        'o_hrefs'       => $oControls['link_hrefs'],
        'o_fbadges'     => $oControls['field_badges'],
        'h_buttons'     => $hControls ? $hControls['buttons'] : "\xe2\x80\x94",
        'h_links'       => $hControls ? $hControls['links'] : "\xe2\x80\x94",
        'h_fields'      => $hControls ? $hControls['total_fields'] : "\xe2\x80\x94",
        'h_headings'    => $hControls ? $hControls['headings'] : "\xe2\x80\x94",
        'h_labels'      => $hControls ? $hControls['labels'] : "\xe2\x80\x94",
        'h_badges'      => $hControls ? $hControls['badges'] : "\xe2\x80\x94",
        'h_icons'       => $hControls ? $hControls['icons'] : "\xe2\x80\x94",
        'h_tables'      => $hControls ? $hControls['tables'] : "\xe2\x80\x94",
        'h_th'          => $hControls ? $hControls['table_cols'] : "\xe2\x80\x94",
        'h_total'       => $hControls ? $hControls['total_controls'] : "\xe2\x80\x94",
        'h_btn_cls'     => $hControls ? $hControls['btn_classes'] : [],
        'h_hrefs'       => $hControls ? $hControls['link_hrefs'] : [],
        'h_fbadges'     => $hControls ? $hControls['field_badges'] : ['required' => 0, 'recommended' => 0, 'optional' => 0],
        'delta'         => $hControls ? ($oControls['total_controls'] - $hControls['total_controls']) : '?',
        'has_heratio'   => !empty($heratioFiles),
    ];

    $allResults[] = $row;
    $totals['files']++;
    $totals['openric_controls'] += $oControls['total_controls'];
    if ($hControls) {
        $totals['heratio_controls'] += $hControls['total_controls'];
        $totals['delta'] += ($oControls['total_controls'] - $hControls['total_controls']);
    } else {
        $totals['missing_heratio']++;
    }
}

// ── JSON output mode ────────────────────────────────────────────────────

if ($jsonOutput) {
    echo json_encode([
        'totals' => $totals,
        'results' => $allResults,
    ], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

// ── Output ───────────────────────────────────────────────────────────────

// Group by package
$byPackage = [];
foreach ($allResults as $r) {
    $byPackage[$r['package']][] = $r;
}
ksort($byPackage);

echo "# FULL CONTROL AUDIT: OpenRiC vs Heratio\n";
echo "# Generated: " . date('Y-m-d H:i:s') . "\n";
echo "# Total OpenRiC views: {$totals['files']}\n";
echo "# Views with Heratio equivalent: " . ($totals['files'] - $totals['missing_heratio']) . "\n";
echo "# Views without Heratio equivalent: {$totals['missing_heratio']}\n";
echo "# Total OpenRiC controls: {$totals['openric_controls']}\n";
echo "# Total Heratio controls (matched): {$totals['heratio_controls']}\n";
echo "# Total delta: {$totals['delta']}\n\n";

// Summary table
echo "## SUMMARY BY PACKAGE\n\n";
echo str_pad('Package', 35) . str_pad('Views', 6) . str_pad('O-Ctrl', 8) . str_pad('H-Ctrl', 8) . str_pad('Delta', 8) . str_pad('Layout', 10) . str_pad('Sidebar', 10) . str_pad('Heratio?', 9) . "\n";
echo str_repeat("\xe2\x94\x80", 94) . "\n";

foreach ($byPackage as $pkg => $rows) {
    $oTotal = array_sum(array_column($rows, 'o_total'));
    $hTotal = 0;
    $hasHeratio = 0;
    foreach ($rows as $r) {
        if ($r['has_heratio']) {
            $hTotal += $r['h_total'];
            $hasHeratio++;
        }
    }
    $delta = $hasHeratio > 0 ? ($oTotal - $hTotal) : '?';
    $layouts = array_unique(array_column($rows, 'layout'));
    $sidebars = array_unique(array_column($rows, 'sidebar'));

    echo str_pad($pkg, 35)
       . str_pad(count($rows), 6)
       . str_pad($oTotal, 8)
       . str_pad($hasHeratio > 0 ? $hTotal : "\xe2\x80\x94", 8)
       . str_pad($delta, 8)
       . str_pad(implode(',', $layouts), 10)
       . str_pad(implode(',', $sidebars), 10)
       . str_pad("$hasHeratio/" . count($rows), 9)
       . "\n";
}

echo "\n\n## DETAILED PAGE-BY-PAGE AUDIT\n\n";

foreach ($byPackage as $pkg => $rows) {
    echo "### $pkg\n\n";
    echo str_pad('View', 30) . str_pad('Layout', 8) . str_pad('Side', 8)
       . str_pad('O-Btn', 6) . str_pad('O-Lnk', 6) . str_pad('O-Fld', 6) . str_pad('O-Hdg', 6) . str_pad('O-Lbl', 6) . str_pad('O-Bdg', 6) . str_pad('O-Tot', 7)
       . str_pad('H-Btn', 6) . str_pad('H-Lnk', 6) . str_pad('H-Fld', 6) . str_pad('H-Tot', 7)
       . str_pad('Delta', 7) . "\n";
    echo str_repeat("\xe2\x94\x80", 120) . "\n";

    foreach ($rows as $r) {
        echo str_pad($r['view'], 30)
           . str_pad($r['layout'], 8)
           . str_pad($r['sidebar'], 8)
           . str_pad($r['o_buttons'], 6)
           . str_pad($r['o_links'], 6)
           . str_pad($r['o_fields'], 6)
           . str_pad($r['o_headings'], 6)
           . str_pad($r['o_labels'], 6)
           . str_pad($r['o_badges'], 6)
           . str_pad($r['o_total'], 7)
           . str_pad($r['h_buttons'], 6)
           . str_pad($r['h_links'], 6)
           . str_pad($r['h_fields'], 6)
           . str_pad($r['h_total'], 7)
           . str_pad($r['delta'], 7)
           . "\n";

        // Show button classes
        if (!empty($r['o_btn_cls'])) {
            echo "  BTN classes: " . implode(', ', array_map(fn($c, $n) => "$c($n)", array_keys($r['o_btn_cls']), $r['o_btn_cls'])) . "\n";
        }

        // Show field badges
        $fb = $r['o_fbadges'];
        if ($fb['required'] + $fb['recommended'] + $fb['optional'] > 0) {
            echo "  Field badges: Req={$fb['required']} Rec={$fb['recommended']} Opt={$fb['optional']}\n";
        } elseif ($r['o_labels'] > 2) {
            echo "  WARNING: NO FIELD BADGES on {$r['o_labels']} labels\n";
        }

        // Show link hrefs (abbreviated)
        if (!empty($r['o_hrefs'])) {
            $unique = array_unique($r['o_hrefs']);
            $display = array_slice($unique, 0, 10);
            echo "  URLs: " . implode(' | ', $display);
            if (count($unique) > 10) echo " ... +" . (count($unique) - 10) . " more";
            echo "\n";
        }

        // Show Heratio equivalent
        if ($r['heratio_files']) {
            echo "  Heratio: {$r['heratio_files']}\n";
        } else {
            echo "  Heratio: NO EQUIVALENT FOUND\n";
        }
        echo "\n";
    }
    echo "\n";
}

// ── Missing field badges report ──────────────────────────────────────────
echo "\n## MISSING FIELD BADGES REPORT\n\n";
echo str_pad('Package', 35) . str_pad('View', 30) . str_pad('Labels', 8) . str_pad('Req', 5) . str_pad('Rec', 5) . str_pad('Opt', 5) . str_pad('Status', 15) . "\n";
echo str_repeat("\xe2\x94\x80", 103) . "\n";

foreach ($allResults as $r) {
    if ($r['o_labels'] <= 2) continue; // skip files with few labels
    $fb = $r['o_fbadges'];
    $totalBadges = $fb['required'] + $fb['recommended'] + $fb['optional'];
    $status = $totalBadges >= $r['o_labels'] ? 'OK' : ($totalBadges > 0 ? 'PARTIAL' : 'MISSING');
    if ($status === 'OK') continue; // only show problems

    echo str_pad($r['package'], 35)
       . str_pad($r['view'], 30)
       . str_pad($r['o_labels'], 8)
       . str_pad($fb['required'], 5)
       . str_pad($fb['recommended'], 5)
       . str_pad($fb['optional'], 5)
       . str_pad($status, 15)
       . "\n";
}

// ── Button class audit ───────────────────────────────────────────────────
echo "\n\n## BUTTON CLASS AUDIT (non-theme classes)\n\n";
$badBtnFiles = [];
foreach ($allResults as $r) {
    foreach ($r['o_btn_cls'] as $cls => $count) {
        // Flag non-atom-btn classes that should probably be atom-btn-*
        if (preg_match('/^btn-(primary|secondary|success|danger|warning|info|dark|light)$/', $cls)) {
            $badBtnFiles[] = [
                'package' => $r['package'],
                'view' => $r['view'],
                'class' => $cls,
                'count' => $count,
            ];
        }
    }
}

if (empty($badBtnFiles)) {
    echo "All button classes use atom-btn-* theme.\n";
} else {
    echo str_pad('Package', 35) . str_pad('View', 30) . str_pad('Bad Class', 20) . str_pad('Count', 6) . "\n";
    echo str_repeat("\xe2\x94\x80", 91) . "\n";
    foreach ($badBtnFiles as $bf) {
        echo str_pad($bf['package'], 35) . str_pad($bf['view'], 30) . str_pad($bf['class'], 20) . str_pad($bf['count'], 6) . "\n";
    }
}

echo "\n\n# END OF AUDIT\n";
