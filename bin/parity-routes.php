#!/usr/bin/env php
<?php
/**
 * OpenRiC Parity Routes — OpenRiC vs Heratio Route Comparison
 *
 * Reads `php artisan route:list --json` from both OpenRiC and Heratio,
 * compares them, and reports matched/missing/extra routes grouped by package.
 *
 * Usage:
 *   php bin/parity-routes.php [OPTIONS]
 *
 * Options:
 *   --output FILE      Write HTML report (default: /tmp/openric-parity-routes-report.html)
 *   --json             Output JSON instead of terminal text
 *   --filter PATTERN   Only check routes from matching packages
 *   --missing-only     Only show routes missing from OpenRiC
 *   --help             Show usage
 */

// ── Configuration ────────────────────────────────────────────────────────────

$openricRoot = '/usr/share/nginx/OpenRiC';
$heratioRoot = '/usr/share/nginx/heratio';
$outputFile  = '/tmp/openric-parity-routes-report.html';
$jsonOutput  = false;
$filter      = '';
$missingOnly = false;

// ── Parse CLI arguments ──────────────────────────────────────────────────────

$args = array_slice($argv, 1);
for ($i = 0; $i < count($args); $i++) {
    switch ($args[$i]) {
        case '--output':
            $outputFile = $args[++$i] ?? $outputFile;
            break;
        case '--json':
            $jsonOutput = true;
            break;
        case '--filter':
            $filter = $args[++$i] ?? '';
            break;
        case '--missing-only':
            $missingOnly = true;
            break;
        case '--help':
            echo <<<HELP
OpenRiC Parity Routes — OpenRiC vs Heratio Route Comparison

Usage: php bin/parity-routes.php [OPTIONS]

Options:
  --output FILE      Write HTML report (default: /tmp/openric-parity-routes-report.html)
  --json             Output JSON instead of terminal text
  --filter PATTERN   Only check routes from matching packages
  --missing-only     Only show routes missing from OpenRiC
  --help             Show this help

HELP;
            exit(0);
    }
}

// ── Colors (terminal) ────────────────────────────────────────────────────────

define('RED',    "\033[0;31m");
define('GREEN',  "\033[0;32m");
define('YELLOW', "\033[1;33m");
define('BLUE',   "\033[0;34m");
define('CYAN',   "\033[0;36m");
define('BOLD',   "\033[1m");
define('DIM',    "\033[2m");
define('NC',     "\033[0m");

// ── Package prefix mapping ──────────────────────────────────────────────────
// Maps Heratio package prefixes in route actions to OpenRiC equivalents

$packageMap = [
    'ahg-actor-manage' => 'openric-agent-manage',
    'ahg-information-object-manage' => 'openric-record-manage',
    'ahg-repository-manage' => 'openric-repository',
    'ahg-accession-manage' => 'openric-accession',
    'ahg-donor-manage' => 'openric-donor',
    'ahg-rights-holder-manage' => 'openric-rights-holder-manage',
    'ahg-storage-manage' => 'openric-storage-manage',
    'ahg-term-taxonomy' => 'openric-term-taxonomy',
    'ahg-function-manage' => 'openric-function-manage',
    'ahg-user-manage' => 'openric-user-manage',
    'ahg-settings' => 'openric-settings-manage',
    'ahg-search' => 'openric-search',
    'ahg-static-page' => 'openric-static-page',
    'ahg-menu-manage' => 'openric-menu-manage',
    'ahg-reports' => 'openric-reports',
    'ahg-cart' => 'openric-cart',
    'ahg-favorites' => 'openric-favorites',
    'ahg-feedback' => 'openric-feedback',
    'ahg-audit-trail' => 'openric-audit',
    'ahg-backup' => 'openric-backup',
    'ahg-data-migration' => 'openric-data-migration',
    'ahg-display' => 'openric-display',
    'ahg-iiif-collection' => 'openric-digital-object',
    'ahg-request-publish' => 'openric-request-publish',
    'ahg-workflow' => 'openric-workflow',
    'ahg-gallery' => 'openric-gallery',
    'ahg-museum' => 'openric-museum',
    'ahg-library' => 'openric-library',
    'ahg-dam' => 'openric-dam',
    'ahg-research' => 'openric-research',
    'ahg-researcher-manage' => 'openric-researcher-manage',
    'ahg-dedupe' => 'openric-dedupe',
    'ahg-doi-manage' => 'openric-doi-manage',
    'ahg-loan' => 'openric-loan',
    'ahg-preservation' => 'openric-preservation',
    'ahg-ai-services' => 'openric-ai',
    'ahg-3d-model' => 'openric-3d-model',
    'ahg-ftp-upload' => 'openric-ftp-upload',
    'ahg-help' => 'openric-help',
    'ahg-integrity' => 'openric-integrity',
    'ahg-metadata-extraction' => 'openric-metadata-extraction',
    'ahg-portable-export' => 'openric-portable-export',
    'ahg-ric' => 'openric-ric',
    'ahg-acl' => 'openric-auth',
    'ahg-heritage-manage' => 'openric-heritage',
    'ahg-condition' => 'openric-condition',
    'ahg-dropdown-manage' => 'openric-dropdown-manage',
    'ahg-ingest' => 'openric-ingest',
    'ahg-export' => 'openric-export',
    'ahg-jobs-manage' => 'openric-jobs-manage',
    'ahg-media-processing' => 'openric-media-processing',
    'ahg-pdf-tools' => 'openric-pdf-tools',
    'ahg-theme-b5' => 'openric-theme',
    'ahg-core' => 'openric-core',
    'ahg-api-plugin' => 'openric-api',
    'ahg-access-request' => 'openric-access-request',
    'ahg-privacy' => 'openric-privacy',
    'ahg-spectrum' => 'openric-spectrum',
    'ahg-icip' => 'openric-icip',
    'ahg-naz' => 'openric-naz',
    'ahg-nmmz' => 'openric-nmmz',
    'ahg-exhibition' => 'openric-exhibition',
    'ahg-ipsas' => 'openric-ipsas',
    'ahg-semantic-search' => 'openric-semantic-search',
    'ahg-statistics' => 'openric-statistics',
    'ahg-multi-tenant' => 'openric-multi-tenant',
    'ahg-landing-page' => 'openric-landing-page',
    'ahg-forms' => 'openric-forms',
    'ahg-label' => 'openric-label',
    'ahg-gis' => 'openric-gis',
    'ahg-translation' => 'openric-translation',
    'ahg-graphql' => 'openric-graphql',
    'ahg-discovery' => 'openric-discovery',
    'ahg-vendor' => 'openric-vendor',
    'ahg-cdpa' => 'openric-cdpa',
    'ahg-registry' => 'openric-registry',
    'ahg-marketplace' => 'openric-marketplace',
    'ahg-custom-fields' => 'openric-custom-fields',
    'ahg-metadata-export' => 'openric-metadata-export',
    'ahg-provenance' => 'openric-provenance',
    'ahg-federation' => 'openric-federation',
    'ahg-security-clearance' => 'openric-auth',
];

// ── Step 1: Get Heratio routes ──────────────────────────────────────────────

echo BLUE . "Loading Heratio routes..." . NC . "\n";

$heratioJson = shell_exec("php " . __DIR__ . "/lib/route-extract.php --app {$heratioRoot} 2>/dev/null");
$heratioRoutes = json_decode($heratioJson, true) ?: [];

// Build lookup: normalized URI => route info
$heratioLookup = [];
$heratioUris = [];
foreach ($heratioRoutes as $route) {
    $method = $route['method'] ?? '';
    $uri = ltrim($route['uri'] ?? '', '/');
    $name = $route['name'] ?? '';
    $action = $route['action'] ?? '';

    // Normalize: replace {param} with :param for comparison
    $normalized = preg_replace('/\{([^}]+)\}/', ':$1', $uri);
    $heratioLookup[$normalized] = [
        'method' => $method,
        'uri'    => $uri,
        'name'   => $name,
        'action' => $action,
    ];
    $heratioUris[] = $normalized;
}

echo GREEN . "  Found " . count($heratioRoutes) . " Heratio routes" . NC . "\n";

// ── Step 2: Get OpenRiC routes ──────────────────────────────────────────────

echo BLUE . "Loading OpenRiC routes..." . NC . "\n";

$openricJson = shell_exec("php " . __DIR__ . "/lib/route-extract.php --app {$openricRoot} 2>/dev/null");
$openricRoutes = json_decode($openricJson, true) ?: [];

// Build lookup: normalized URI => route info
$openricLookup = [];
$openricUris = [];
foreach ($openricRoutes as $route) {
    $method = $route['method'] ?? '';
    $uri = ltrim($route['uri'] ?? '', '/');
    $name = $route['name'] ?? '';
    $action = $route['action'] ?? '';

    $normalized = preg_replace('/\{([^}]+)\}/', ':$1', $uri);
    $openricLookup[$normalized] = [
        'method' => $method,
        'uri'    => $uri,
        'name'   => $name,
        'action' => $action,
    ];
    $openricUris[] = $normalized;
}

echo GREEN . "  Found " . count($openricRoutes) . " OpenRiC routes" . NC . "\n";

/**
 * Guess which package a route belongs to based on its action class or name.
 */
function guessPackage(array $route): string
{
    $action = $route['action'] ?? '';
    $name = $route['name'] ?? '';
    $uri = $route['uri'] ?? '';

    // Try to match from action namespace
    if (preg_match('/\\\\([A-Za-z]+)Controller/', $action, $m)) {
        $controller = $m[1];
        // Map controller names to package names
        $controllerMap = [
            'Accession' => 'accession',
            'Actor' => 'agent-manage',
            'Agent' => 'agent-manage',
            'InformationObject' => 'record-manage',
            'Record' => 'record-manage',
            'Repository' => 'repository',
            'Donor' => 'donor',
            'RightsHolder' => 'rights-holder-manage',
            'Storage' => 'storage-manage',
            'PhysicalObject' => 'storage-manage',
            'Term' => 'term-taxonomy',
            'Taxonomy' => 'term-taxonomy',
            'Function' => 'function-manage',
            'User' => 'user-manage',
            'Settings' => 'settings-manage',
            'Search' => 'search',
            'StaticPage' => 'static-page',
            'Menu' => 'menu-manage',
            'Report' => 'reports',
            'Cart' => 'cart',
            'Clipboard' => 'cart',
            'Favorites' => 'favorites',
            'Feedback' => 'feedback',
            'Audit' => 'audit',
            'Backup' => 'backup',
            'DataMigration' => 'data-migration',
            'Display' => 'display',
            'Iiif' => 'digital-object',
            'DigitalObject' => 'digital-object',
            'Workflow' => 'workflow',
            'Gallery' => 'gallery',
            'Museum' => 'museum',
            'Library' => 'library',
            'Dam' => 'dam',
            'Research' => 'research',
            'Researcher' => 'researcher-manage',
            'Dedupe' => 'dedupe',
            'Doi' => 'doi-manage',
            'Loan' => 'loan',
            'Preservation' => 'preservation',
            'Heritage' => 'heritage',
            'Condition' => 'condition',
            'Acl' => 'auth',
            'Dropdown' => 'dropdown-manage',
            'Ingest' => 'ingest',
            'Export' => 'export',
            'Job' => 'jobs-manage',
            'Api' => 'api',
            'Help' => 'help',
        ];
        foreach ($controllerMap as $prefix => $pkg) {
            if (stripos($controller, $prefix) === 0) {
                return $pkg;
            }
        }
    }

    // Try to match from route name prefix
    if ($name) {
        $parts = explode('.', $name);
        if (count($parts) > 1) {
            return $parts[0];
        }
    }

    // Try to match from URI prefix
    $uriParts = explode('/', ltrim($uri, '/'));
    if (!empty($uriParts[0])) {
        return $uriParts[0];
    }

    return 'unknown';
}

// ── Step 3: Compare routes ──────────────────────────────────────────────────

echo BLUE . "\nComparing routes..." . NC . "\n\n";

$results = [
    'matched' => [],
    'missing' => [],
    'extra'   => [],
];

// For each Heratio route, check if OpenRiC has a matching route
foreach ($heratioLookup as $normalized => $hRoute) {
    $pkg = guessPackage($hRoute);

    // Apply filter
    if ($filter && stripos($pkg, $filter) === false && stripos($normalized, $filter) === false) {
        continue;
    }

    $found = false;
    $matchedOpenric = null;

    // Direct match
    if (isset($openricLookup[$normalized])) {
        $found = true;
        $matchedOpenric = $openricLookup[$normalized];
    }

    // Fuzzy match: normalize both to wildcard form and compare
    if (!$found) {
        $hNorm = preg_replace('/:[\w]+/', '*', $normalized);
        foreach ($openricUris as $oUri) {
            $oNorm = preg_replace('/:[\w]+/', '*', $oUri);
            if ($hNorm === $oNorm) {
                $found = true;
                $matchedOpenric = $openricLookup[$oUri];
                break;
            }
        }
    }

    if ($found) {
        $results['matched'][] = [
            'heratio_uri'   => $hRoute['uri'],
            'heratio_name'  => $hRoute['name'],
            'openric_uri'   => $matchedOpenric['uri'] ?? '',
            'openric_name'  => $matchedOpenric['name'] ?? '',
            'package'       => $pkg,
        ];
    } else {
        $results['missing'][] = [
            'heratio_uri'    => $hRoute['uri'],
            'heratio_name'   => $hRoute['name'],
            'heratio_action' => $hRoute['action'],
            'heratio_method' => $hRoute['method'],
            'package'        => $pkg,
        ];
    }
}

// Find OpenRiC routes with no Heratio equivalent (extra)
foreach ($openricLookup as $normalized => $oRoute) {
    $foundInHeratio = false;

    if (isset($heratioLookup[$normalized])) {
        $foundInHeratio = true;
    }

    if (!$foundInHeratio) {
        $oNorm = preg_replace('/:[\w]+/', '*', $normalized);
        foreach ($heratioUris as $hUri) {
            $hNorm = preg_replace('/:[\w]+/', '*', $hUri);
            if ($oNorm === $hNorm) {
                $foundInHeratio = true;
                break;
            }
        }
    }

    if (!$foundInHeratio) {
        $pkg = guessPackage($oRoute);
        if ($filter && stripos($pkg, $filter) === false && stripos($normalized, $filter) === false) {
            continue;
        }
        $results['extra'][] = [
            'openric_uri'    => $oRoute['uri'],
            'openric_name'   => $oRoute['name'],
            'openric_action' => $oRoute['action'],
            'package'        => $pkg,
        ];
    }
}

// ── Step 4: Output results ──────────────────────────────────────────────────

if ($jsonOutput) {
    echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

// Terminal output
$totalHeratio = count($heratioRoutes);
$totalOpenric = count($openricRoutes);
$matchedCount = count($results['matched']);
$missingCount = count($results['missing']);
$extraCount = count($results['extra']);

echo BOLD . CYAN . "=== Route Comparison Summary ===" . NC . "\n";
echo DIM . str_repeat("\xe2\x94\x80", 64) . NC . "\n";
echo "  Heratio routes:           " . BOLD . $totalHeratio . NC . "\n";
echo "  OpenRiC routes:           " . BOLD . $totalOpenric . NC . "\n";
echo "  " . GREEN . "Matched:                  " . $matchedCount . NC . "\n";
echo "  " . RED . "Missing from OpenRiC:     " . $missingCount . NC . "\n";
echo "  " . YELLOW . "Extra in OpenRiC:         " . $extraCount . NC . "\n";
echo DIM . str_repeat("\xe2\x94\x80", 64) . NC . "\n\n";

// Coverage bar
$coveragePct = ($matchedCount + $missingCount) > 0
    ? round(($matchedCount / ($matchedCount + $missingCount)) * 100, 1)
    : 0;
echo "  Route Coverage: " . BOLD . $coveragePct . "%" . NC . "\n\n";

// Missing routes (grouped by package)
if ($missingCount > 0) {
    echo BOLD . RED . "Missing from OpenRiC:" . NC . "\n";
    echo DIM . str_repeat("\xe2\x94\x80", 64) . NC . "\n";

    $byPackage = [];
    foreach ($results['missing'] as $r) {
        $pkg = $r['package'] ?? 'unknown';
        $byPackage[$pkg][] = $r;
    }
    ksort($byPackage);

    foreach ($byPackage as $pkg => $routes) {
        echo "\n  " . BOLD . YELLOW . $pkg . NC . " (" . count($routes) . " routes)\n";
        foreach ($routes as $r) {
            printf("    " . RED . "%-50s" . NC . " " . DIM . "%-30s %s" . NC . "\n",
                '/' . $r['heratio_uri'], $r['heratio_name'], $r['heratio_method']);
        }
    }
    echo "\n";
}

// Matched routes
if (!$missingOnly && $matchedCount > 0) {
    echo BOLD . GREEN . "Matched routes:" . NC . "\n";
    echo DIM . str_repeat("\xe2\x94\x80", 64) . NC . "\n";

    foreach ($results['matched'] as $r) {
        printf("  " . GREEN . "%-45s" . NC . " => " . DIM . "%-45s" . NC . "\n",
            '/' . $r['heratio_uri'], '/' . $r['openric_uri']);
    }
    echo "\n";
}

// Extra OpenRiC routes
if (!$missingOnly && $extraCount > 0) {
    echo BOLD . YELLOW . "Extra in OpenRiC (no Heratio equivalent):" . NC . "\n";
    echo DIM . str_repeat("\xe2\x94\x80", 64) . NC . "\n";

    foreach ($results['extra'] as $r) {
        printf("  " . YELLOW . "%-50s" . NC . " " . DIM . "%s" . NC . "\n",
            '/' . $r['openric_uri'], $r['openric_name']);
    }
    echo "\n";
}

// ── Step 5: Generate HTML report ────────────────────────────────────────────

echo BLUE . "Generating HTML report: {$outputFile}" . NC . "\n";

$timestamp = date('Y-m-d H:i:s');

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OpenRiC Route Parity Report</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #212529; padding: 2rem; }
  h1 { color: #2c3e50; margin-bottom: 0.5rem; }
  h2 { color: #2c3e50; margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #dee2e6; }
  h3 { color: #495057; margin: 1rem 0 0.5rem; }
  .meta { color: #6c757d; margin-bottom: 2rem; font-size: 0.9rem; }
  .summary { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
  .card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.25rem; min-width: 140px; flex: 1; }
  .card .number { font-size: 2rem; font-weight: 700; }
  .card .label { color: #6c757d; font-size: 0.85rem; }
  .card.green .number { color: #198754; }
  .card.yellow .number { color: #ffc107; }
  .card.red .number { color: #dc3545; }
  .card.blue .number { color: #0d6efd; }
  .card.gray .number { color: #6c757d; }
  table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6; margin-bottom: 1rem; }
  th { background: #2c3e50; color: #fff; text-align: left; padding: 0.6rem 0.8rem; font-size: 0.82rem; }
  td { padding: 0.45rem 0.8rem; border-bottom: 1px solid #dee2e6; font-size: 0.82rem; }
  tr:hover { background: #f1f3f5; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 600; }
  .badge-red { background: #f8d7da; color: #842029; }
  .badge-yellow { background: #fff3cd; color: #664d03; }
  .badge-green { background: #d1e7dd; color: #0f5132; }
  .badge-blue { background: #cfe2ff; color: #084298; }
  .badge-gray { background: #e9ecef; color: #495057; }
  .mono { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.78rem; }
  .filter-bar { margin-bottom: 1rem; }
  .filter-bar input { padding: 0.5rem 1rem; border: 1px solid #dee2e6; border-radius: 4px; width: 300px; }
  .pct-bar { display: inline-block; height: 8px; border-radius: 4px; }
  .pct-green { background: #198754; }
  .pct-red { background: #dc3545; }
  @media (max-width: 768px) { .summary { flex-direction: column; } }
</style>
</head>
<body>
<h1>OpenRiC Route Parity Report</h1>
<p class="meta">Generated: {$timestamp} | OpenRiC: {$openricRoot} | Heratio: {$heratioRoot}</p>

<div class="summary">
  <div class="card blue"><div class="number">{$totalHeratio}</div><div class="label">Heratio Routes</div></div>
  <div class="card blue"><div class="number">{$totalOpenric}</div><div class="label">OpenRiC Routes</div></div>
  <div class="card green"><div class="number">{$matchedCount}</div><div class="label">Matched</div></div>
  <div class="card red"><div class="number">{$missingCount}</div><div class="label">Missing from OpenRiC</div></div>
  <div class="card yellow"><div class="number">{$extraCount}</div><div class="label">Extra in OpenRiC</div></div>
</div>

HTML;

// Coverage bar
$html .= "<p style='margin-bottom:2rem;'><strong>Route Coverage:</strong> {$coveragePct}% ";
$html .= "<span class='pct-bar pct-green' style='width:{$coveragePct}px;'></span>";
$html .= "<span class='pct-bar pct-red' style='width:" . (100 - $coveragePct) . "px;'></span></p>\n";

// Package summary table
$pkgSummary = [];
foreach ($results['matched'] as $r) {
    $pkg = $r['package'] ?? 'unknown';
    if (!isset($pkgSummary[$pkg])) $pkgSummary[$pkg] = ['matched' => 0, 'missing' => 0, 'extra' => 0];
    $pkgSummary[$pkg]['matched']++;
}
foreach ($results['missing'] as $r) {
    $pkg = $r['package'] ?? 'unknown';
    if (!isset($pkgSummary[$pkg])) $pkgSummary[$pkg] = ['matched' => 0, 'missing' => 0, 'extra' => 0];
    $pkgSummary[$pkg]['missing']++;
}
foreach ($results['extra'] as $r) {
    $pkg = $r['package'] ?? 'unknown';
    if (!isset($pkgSummary[$pkg])) $pkgSummary[$pkg] = ['matched' => 0, 'missing' => 0, 'extra' => 0];
    $pkgSummary[$pkg]['extra']++;
}
ksort($pkgSummary);

$html .= "<h2>Summary by Package</h2>\n";
$html .= "<table>\n<thead><tr><th>Package</th><th>Matched</th><th>Missing</th><th>Extra</th><th>Coverage</th></tr></thead>\n<tbody>\n";
foreach ($pkgSummary as $pkg => $counts) {
    $total = $counts['matched'] + $counts['missing'];
    $pct = $total > 0 ? round(($counts['matched'] / $total) * 100, 1) : 100;
    $pctBadge = $pct >= 90 ? 'badge-green' : ($pct >= 50 ? 'badge-yellow' : 'badge-red');
    $html .= "<tr><td><strong>" . htmlspecialchars($pkg) . "</strong></td>"
           . "<td>{$counts['matched']}</td>"
           . "<td>{$counts['missing']}</td>"
           . "<td>{$counts['extra']}</td>"
           . "<td><span class='badge {$pctBadge}'>{$pct}%</span></td></tr>\n";
}
$html .= "</tbody></table>\n";

// Missing routes section
if ($missingCount > 0) {
    $html .= "<h2>Missing from OpenRiC ({$missingCount})</h2>\n";
    $html .= "<div class='filter-bar'><input type='text' id='filterMissing' placeholder='Filter missing routes...' onkeyup=\"filterRows('missingTable','filterMissing')\"></div>\n";
    $html .= "<table id='missingTable'>\n<thead><tr><th>Heratio URI</th><th>Route Name</th><th>Method</th><th>Action</th><th>Package</th></tr></thead>\n<tbody>\n";

    usort($results['missing'], function($a, $b) {
        $c = strcmp($a['package'], $b['package']);
        return $c !== 0 ? $c : strcmp($a['heratio_uri'], $b['heratio_uri']);
    });

    foreach ($results['missing'] as $r) {
        $uri = htmlspecialchars($r['heratio_uri']);
        $name = htmlspecialchars($r['heratio_name']);
        $method = htmlspecialchars($r['heratio_method']);
        $action = htmlspecialchars($r['heratio_action']);
        $pkg = htmlspecialchars($r['package']);
        $html .= "<tr><td class='mono'>/{$uri}</td><td>{$name}</td><td>{$method}</td><td class='mono'>{$action}</td><td><span class='badge badge-blue'>{$pkg}</span></td></tr>\n";
    }
    $html .= "</tbody></table>\n";
}

// Matched routes section
if ($matchedCount > 0) {
    $html .= "<h2>Matched Routes ({$matchedCount})</h2>\n";
    $html .= "<div class='filter-bar'><input type='text' id='filterMatched' placeholder='Filter matched routes...' onkeyup=\"filterRows('matchedTable','filterMatched')\"></div>\n";
    $html .= "<table id='matchedTable'>\n<thead><tr><th>Heratio URI</th><th>OpenRiC URI</th><th>Heratio Route</th><th>OpenRiC Route</th><th>Package</th></tr></thead>\n<tbody>\n";

    foreach ($results['matched'] as $r) {
        $hUri = htmlspecialchars($r['heratio_uri']);
        $oUri = htmlspecialchars($r['openric_uri']);
        $hName = htmlspecialchars($r['heratio_name']);
        $oName = htmlspecialchars($r['openric_name']);
        $pkg = htmlspecialchars($r['package']);
        $html .= "<tr><td class='mono'>/{$hUri}</td><td class='mono'>/{$oUri}</td><td>{$hName}</td><td>{$oName}</td><td><span class='badge badge-gray'>{$pkg}</span></td></tr>\n";
    }
    $html .= "</tbody></table>\n";
}

// Extra OpenRiC routes section
if ($extraCount > 0) {
    $html .= "<h2>Extra OpenRiC Routes ({$extraCount})</h2>\n";
    $html .= "<table>\n<thead><tr><th>OpenRiC URI</th><th>Route Name</th><th>Action</th><th>Package</th></tr></thead>\n<tbody>\n";

    foreach ($results['extra'] as $r) {
        $uri = htmlspecialchars($r['openric_uri']);
        $name = htmlspecialchars($r['openric_name']);
        $action = htmlspecialchars($r['openric_action']);
        $pkg = htmlspecialchars($r['package']);
        $html .= "<tr><td class='mono'>/{$uri}</td><td>{$name}</td><td class='mono'>{$action}</td><td><span class='badge badge-yellow'>{$pkg}</span></td></tr>\n";
    }
    $html .= "</tbody></table>\n";
}

$html .= <<<HTML

<script>
function filterRows(tableId, inputId) {
  var input = document.getElementById(inputId).value.toLowerCase();
  var rows = document.querySelectorAll('#' + tableId + ' tbody tr');
  rows.forEach(function(row) {
    row.style.display = row.textContent.toLowerCase().includes(input) ? '' : 'none';
  });
}
</script>
</body>
</html>
HTML;

file_put_contents($outputFile, $html);
echo GREEN . "HTML report written to: {$outputFile}" . NC . "\n\n";
echo BOLD . "Done." . NC . "\n";
