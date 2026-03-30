#!/usr/bin/env php
<?php
/**
 * OpenRiC Field Parity Check — Edit/Create Form Field Comparison
 *
 * Compares name= attributes between Heratio blade templates and OpenRiC blades
 * for all edit/create views. Reports missing and extra fields per form.
 *
 * Usage:
 *   php bin/parity-check.php [OPTIONS]
 *
 * Options:
 *   --filter PACKAGE   Only check matching package(s)
 *   --json             Output JSON instead of terminal text
 *   --verbose          Show field lists for all forms (not just mismatches)
 *   --html FILE        Write HTML report to file
 *   --help             Show usage
 */

$openricBase = '/usr/share/nginx/OpenRiC/packages';
$heratioBase = '/usr/share/nginx/heratio/packages';

// Parse CLI arguments
$filterPkg = '';
$jsonOutput = false;
$verbose = false;
$htmlFile = '';

$args = array_slice($argv, 1);
for ($i = 0; $i < count($args); $i++) {
    switch ($args[$i]) {
        case '--filter':
            $filterPkg = $args[++$i] ?? '';
            break;
        case '--json':
            $jsonOutput = true;
            break;
        case '--verbose':
            $verbose = true;
            break;
        case '--html':
            $htmlFile = $args[++$i] ?? '/tmp/openric-field-parity.html';
            break;
        case '--help':
            echo <<<HELP
OpenRiC Field Parity Check — Edit/Create Form Field Comparison

Usage: php bin/parity-check.php [OPTIONS]

Options:
  --filter PACKAGE   Only check matching package(s)
  --json             Output JSON instead of terminal text
  --verbose          Show field lists for all forms (not just mismatches)
  --html FILE        Write HTML report to file
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

// ── View names that indicate edit/create forms ──────────────────────────────

$editViewNames = ['edit', 'create', 'add', 'new', 'update'];

// ── Helpers ──────────────────────────────────────────────────────────────

/**
 * Find matching Heratio blade files for a given OpenRiC package and view.
 */
function findHeratio(string $openricPkg, string $viewBase): array
{
    global $heratioBase, $mapping;

    if (!isset($mapping[$openricPkg])) {
        return [];
    }

    $heratioPkgs = $mapping[$openricPkg];
    if (empty($heratioPkgs)) {
        return [];
    }

    // View name mapping: OpenRiC view -> possible Heratio view names
    $viewMap = [
        'edit'   => ['edit', 'update'],
        'create' => ['create', 'add', 'new', 'edit'],
        'add'    => ['add', 'create', 'new', 'edit'],
        'new'    => ['new', 'create', 'add', 'edit'],
        'update' => ['update', 'edit'],
    ];

    $candidates = $viewMap[$viewBase] ?? [$viewBase];

    $found = [];

    foreach ($heratioPkgs as $heratioPkg) {
        $heratioViewDir = "$heratioBase/$heratioPkg/resources/views";
        if (!is_dir($heratioViewDir)) {
            continue;
        }

        foreach ($candidates as $c) {
            // Check root views directory
            $f = "$heratioViewDir/$c.blade.php";
            if (file_exists($f) && !in_array($f, $found)) {
                $found[] = $f;
            }

            // Check subdirectories
            $subdirs = glob("$heratioViewDir/*/", GLOB_ONLYDIR);
            foreach ($subdirs as $subdir) {
                $f = "$subdir$c.blade.php";
                if (file_exists($f) && !in_array($f, $found)) {
                    $found[] = $f;
                }
            }
        }
    }

    return $found;
}

/**
 * Extract field names from name= attributes in HTML/Blade content.
 * Normalizes array-style names: foo[0][bar] -> bar, foo[bar] -> bar
 * Filters out framework/meta fields.
 */
function extractFields(string $content): array
{
    $fields = [];

    // Match name="..." and name='...' attributes
    if (preg_match_all('/\bname\s*=\s*["\']([^"\']+)["\']/i', $content, $m)) {
        foreach ($m[1] as $name) {
            // Normalize array-style field names
            $name = preg_replace('/\w+\[\d+\]\[(\w+)\]/', '$1', $name);
            $name = preg_replace('/\w+\[(\w+)\]/', '$1', $name);

            // Skip framework/meta fields
            $skipFields = [
                '_token', '_method', 'sf_method', 'next', 'csrf_token',
                'MAX_FILE_SIZE', 'topLod', 'sort', 'page', 'limit',
                'subqueryField', 'subqueryOperator', 'subqueryQuery',
                'sq0', 'so0', 'sf0', 'sq1', 'so1', 'sf1',
            ];
            if (in_array($name, $skipFields)) {
                continue;
            }

            // Skip Blade expression names (contain {{ }})
            if (strpos($name, '{{') !== false || strpos($name, '{!!') !== false) {
                continue;
            }

            $fields[] = $name;
        }
    }

    // Also detect Blade component-style field bindings
    if (preg_match_all('/:?wire:model(?:\.defer|\.lazy)?\s*=\s*["\']([^"\']+)["\']/i', $content, $m)) {
        foreach ($m[1] as $f) {
            $fields[] = $f;
        }
    }

    return array_values(array_unique($fields));
}

/**
 * Detect the form type (edit, create, etc.) from the view content.
 */
function detectFormType(string $content): string
{
    if (preg_match('/method\s*=\s*["\']PUT["\']/i', $content)) return 'edit';
    if (preg_match('/method\s*=\s*["\']PATCH["\']/i', $content)) return 'edit';
    if (preg_match('/@method\s*\(\s*["\']PUT["\']\s*\)/i', $content)) return 'edit';
    if (preg_match('/@method\s*\(\s*["\']PATCH["\']\s*\)/i', $content)) return 'edit';
    if (preg_match('/action\s*=.*store/i', $content)) return 'create';
    if (preg_match('/action\s*=.*update/i', $content)) return 'edit';
    return 'unknown';
}

// ── Scan all edit/create views ──────────────────────────────────────────

$packages = glob("$openricBase/*/", GLOB_ONLYDIR);
sort($packages);

$results = [];
$totalMissing = 0;
$totalExtra = 0;
$totalForms = 0;
$totalPerfect = 0;

foreach ($packages as $pkgPath) {
    $pkg = basename($pkgPath);

    // Apply filter
    if ($filterPkg && stripos($pkg, $filterPkg) === false) {
        continue;
    }

    $viewDir = "$pkgPath/resources/views";
    if (!is_dir($viewDir)) {
        continue;
    }

    // Recursively find blade files
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($it as $f) {
        if (!preg_match('/\.blade\.php$/', $f->getFilename())) {
            continue;
        }

        $viewName = str_replace([$viewDir . '/', '.blade.php'], '', $f->getPathname());
        $viewBase = basename($viewName);

        // Only process edit/create views
        if (!in_array($viewBase, $editViewNames)) {
            continue;
        }

        $oContent = file_get_contents($f->getPathname());
        $oFields = extractFields($oContent);
        $formType = detectFormType($oContent);

        // Find Heratio equivalent
        $heratioFiles = findHeratio($pkg, $viewBase);

        if (empty($heratioFiles)) {
            // No Heratio equivalent found - still record the OpenRiC fields
            $results[] = [
                'pkg'      => $pkg,
                'view'     => $viewName,
                'type'     => $formType,
                'o_count'  => count($oFields),
                'h_count'  => 0,
                'o_fields' => $oFields,
                'h_fields' => [],
                'missing'  => [],
                'extra'    => $oFields,
                'heratio'  => [],
                'matched'  => false,
            ];
            $totalForms++;
            continue;
        }

        // Merge content from all matching Heratio files
        $hContent = '';
        foreach ($heratioFiles as $hf) {
            $hContent .= file_get_contents($hf) . "\n";
        }
        $hFields = extractFields($hContent);

        // Compute differences
        $missing = array_values(array_diff($hFields, $oFields));
        $extra = array_values(array_diff($oFields, $hFields));

        $results[] = [
            'pkg'      => $pkg,
            'view'     => $viewName,
            'type'     => $formType,
            'o_count'  => count($oFields),
            'h_count'  => count($hFields),
            'o_fields' => $oFields,
            'h_fields' => $hFields,
            'missing'  => $missing,
            'extra'    => $extra,
            'heratio'  => array_map(fn($f) => str_replace($heratioBase . '/', '', $f), $heratioFiles),
            'matched'  => true,
        ];

        $totalMissing += count($missing);
        $totalExtra += count($extra);
        $totalForms++;
        if (empty($missing) && empty($extra)) {
            $totalPerfect++;
        }
    }
}

// ── JSON output ─────────────────────────────────────────────────────────

if ($jsonOutput) {
    echo json_encode([
        'summary' => [
            'total_forms' => $totalForms,
            'total_missing_fields' => $totalMissing,
            'total_extra_fields' => $totalExtra,
            'perfect_parity' => $totalPerfect,
        ],
        'results' => $results,
    ], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

// ── Terminal output ─────────────────────────────────────────────────────

echo "# FIELD PARITY: Edit/Create Forms (OpenRiC vs Heratio)\n";
echo "# Generated: " . date('Y-m-d H:i:s') . "\n";
echo "# Forms compared: $totalForms\n";
echo "# Perfect parity: $totalPerfect\n";
echo "# Total missing fields (in Heratio but not OpenRiC): $totalMissing\n";
echo "# Total extra fields (in OpenRiC but not Heratio): $totalExtra\n\n";

// Summary table
echo "## SUMMARY BY PACKAGE\n\n";
echo str_pad('Package', 35) . str_pad('Forms', 7) . str_pad('O-Fld', 7) . str_pad('H-Fld', 7) . str_pad('Miss', 6) . str_pad('Extra', 6) . str_pad('Status', 10) . "\n";
echo str_repeat("\xe2\x94\x80", 78) . "\n";

$byPkg = [];
foreach ($results as $r) {
    if (!isset($byPkg[$r['pkg']])) {
        $byPkg[$r['pkg']] = ['forms' => 0, 'o_fields' => 0, 'h_fields' => 0, 'missing' => 0, 'extra' => 0, 'perfect' => 0];
    }
    $byPkg[$r['pkg']]['forms']++;
    $byPkg[$r['pkg']]['o_fields'] += $r['o_count'];
    $byPkg[$r['pkg']]['h_fields'] += $r['h_count'];
    $byPkg[$r['pkg']]['missing'] += count($r['missing']);
    $byPkg[$r['pkg']]['extra'] += count($r['extra']);
    if (empty($r['missing']) && empty($r['extra'])) {
        $byPkg[$r['pkg']]['perfect']++;
    }
}
ksort($byPkg);

foreach ($byPkg as $pkg => $s) {
    $status = $s['missing'] === 0 && $s['extra'] === 0 ? 'PARITY' : ($s['missing'] > 0 ? 'MISSING' : 'EXTRA');
    echo str_pad($pkg, 35)
       . str_pad($s['forms'], 7)
       . str_pad($s['o_fields'], 7)
       . str_pad($s['h_fields'], 7)
       . str_pad($s['missing'], 6)
       . str_pad($s['extra'], 6)
       . str_pad($status, 10)
       . "\n";
}

// Detailed per-form output
echo "\n\n## DETAILED FORM-BY-FORM AUDIT\n\n";

echo str_pad('Package', 35) . str_pad('View', 20) . str_pad('Type', 8) . str_pad('O-Fld', 7) . str_pad('H-Fld', 7) . str_pad('Miss', 6) . str_pad('Extra', 6) . "\n";
echo str_repeat("\xe2\x94\x80", 89) . "\n";

foreach ($results as $r) {
    $status = count($r['missing']) === 0 && count($r['extra']) === 0 ? ' OK' : ' !!';
    echo str_pad($r['pkg'], 35) . str_pad($r['view'], 20)
       . str_pad($r['type'], 8)
       . str_pad($r['o_count'], 7) . str_pad($r['h_count'], 7)
       . str_pad(count($r['missing']), 6) . str_pad(count($r['extra']), 6)
       . $status . "\n";

    if (!empty($r['missing'])) {
        echo "  MISSING (in Heratio, not in OpenRiC): " . implode(', ', $r['missing']) . "\n";
    }
    if (!empty($r['extra'])) {
        echo "  EXTRA (in OpenRiC, not in Heratio):   " . implode(', ', $r['extra']) . "\n";
    }

    if ($verbose || !empty($r['missing']) || !empty($r['extra'])) {
        if (!empty($r['o_fields'])) {
            echo "  OpenRiC fields: " . implode(', ', $r['o_fields']) . "\n";
        }
        if (!empty($r['h_fields'])) {
            echo "  Heratio fields: " . implode(', ', $r['h_fields']) . "\n";
        }
    }

    echo "  Heratio: " . (!empty($r['heratio']) ? implode(', ', $r['heratio']) : 'NO EQUIVALENT') . "\n";
    echo "\n";
}

// ── Missing fields summary ──────────────────────────────────────────────

$allMissingFields = [];
foreach ($results as $r) {
    foreach ($r['missing'] as $field) {
        if (!isset($allMissingFields[$field])) {
            $allMissingFields[$field] = [];
        }
        $allMissingFields[$field][] = $r['pkg'] . '/' . $r['view'];
    }
}

if (!empty($allMissingFields)) {
    echo "\n## MOST COMMONLY MISSING FIELDS\n\n";
    arsort($allMissingFields);
    echo str_pad('Field Name', 30) . str_pad('Count', 7) . "Found In\n";
    echo str_repeat("\xe2\x94\x80", 80) . "\n";

    $shown = 0;
    foreach ($allMissingFields as $field => $locations) {
        echo str_pad($field, 30) . str_pad(count($locations), 7) . implode(', ', array_slice($locations, 0, 3));
        if (count($locations) > 3) echo " ..." . (count($locations) - 3) . " more";
        echo "\n";
        $shown++;
        if ($shown >= 50) break;
    }
}

// ── HTML report ─────────────────────────────────────────────────────────

if ($htmlFile) {
    $timestamp = date('Y-m-d H:i:s');
    $matchedForms = count(array_filter($results, fn($r) => $r['matched']));
    $unmatchedForms = count(array_filter($results, fn($r) => !$r['matched']));

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OpenRiC Field Parity Report</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #212529; padding: 2rem; }
  h1 { color: #2c3e50; margin-bottom: 0.5rem; }
  h2 { color: #2c3e50; margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #dee2e6; }
  .meta { color: #6c757d; margin-bottom: 2rem; font-size: 0.9rem; }
  .summary { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
  .card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.25rem; min-width: 140px; flex: 1; }
  .card .number { font-size: 2rem; font-weight: 700; }
  .card .label { color: #6c757d; font-size: 0.85rem; }
  .card.green .number { color: #198754; }
  .card.yellow .number { color: #ffc107; }
  .card.red .number { color: #dc3545; }
  .card.blue .number { color: #0d6efd; }
  table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6; margin-bottom: 1rem; }
  th { background: #2c3e50; color: #fff; text-align: left; padding: 0.6rem 0.8rem; font-size: 0.82rem; }
  td { padding: 0.45rem 0.8rem; border-bottom: 1px solid #dee2e6; font-size: 0.82rem; }
  tr:hover { background: #f1f3f5; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 600; }
  .badge-red { background: #f8d7da; color: #842029; }
  .badge-yellow { background: #fff3cd; color: #664d03; }
  .badge-green { background: #d1e7dd; color: #0f5132; }
  .badge-blue { background: #cfe2ff; color: #084298; }
  .mono { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.78rem; }
  .field-list { font-size: 0.78rem; color: #495057; margin: 0.25rem 0; }
  .field-missing { color: #dc3545; font-weight: 600; }
  .field-extra { color: #ffc107; font-weight: 600; }
  .filter-bar { margin-bottom: 1rem; }
  .filter-bar input { padding: 0.5rem 1rem; border: 1px solid #dee2e6; border-radius: 4px; width: 300px; }
</style>
</head>
<body>
<h1>OpenRiC Field Parity Report</h1>
<p class="meta">Generated: {$timestamp}</p>

<div class="summary">
  <div class="card blue"><div class="number">{$totalForms}</div><div class="label">Total Forms</div></div>
  <div class="card green"><div class="number">{$totalPerfect}</div><div class="label">Perfect Parity</div></div>
  <div class="card red"><div class="number">{$totalMissing}</div><div class="label">Missing Fields</div></div>
  <div class="card yellow"><div class="number">{$totalExtra}</div><div class="label">Extra Fields</div></div>
  <div class="card blue"><div class="number">{$matchedForms}</div><div class="label">Matched to Heratio</div></div>
</div>

<h2>Package Summary</h2>
<table>
<thead><tr><th>Package</th><th>Forms</th><th>OpenRiC Fields</th><th>Heratio Fields</th><th>Missing</th><th>Extra</th><th>Status</th></tr></thead>
<tbody>
HTML;

    foreach ($byPkg as $pkg => $s) {
        $status = $s['missing'] === 0 && $s['extra'] === 0 ? 'PARITY' : ($s['missing'] > 0 ? 'MISSING' : 'EXTRA');
        $badgeClass = $status === 'PARITY' ? 'badge-green' : ($status === 'MISSING' ? 'badge-red' : 'badge-yellow');
        $html .= "<tr><td><strong>" . htmlspecialchars($pkg) . "</strong></td>"
               . "<td>{$s['forms']}</td><td>{$s['o_fields']}</td><td>{$s['h_fields']}</td>"
               . "<td>{$s['missing']}</td><td>{$s['extra']}</td>"
               . "<td><span class='badge {$badgeClass}'>{$status}</span></td></tr>\n";
    }

    $html .= "</tbody></table>\n";

    // Detailed forms
    $html .= "<h2>Form Details</h2>\n";
    $html .= "<div class='filter-bar'><input type='text' id='filterForms' placeholder='Filter forms...' onkeyup=\"filterRows('formsTable','filterForms')\"></div>\n";
    $html .= "<table id='formsTable'>\n<thead><tr><th>Package</th><th>View</th><th>Type</th><th>O-Fields</th><th>H-Fields</th><th>Missing</th><th>Extra</th><th>Details</th></tr></thead>\n<tbody>\n";

    foreach ($results as $r) {
        $missingStr = !empty($r['missing']) ? '<span class="field-missing">' . htmlspecialchars(implode(', ', $r['missing'])) . '</span>' : '';
        $extraStr = !empty($r['extra']) ? '<span class="field-extra">' . htmlspecialchars(implode(', ', $r['extra'])) . '</span>' : '';
        $detailParts = [];
        if ($missingStr) $detailParts[] = "Missing: $missingStr";
        if ($extraStr) $detailParts[] = "Extra: $extraStr";
        $detail = implode('<br>', $detailParts);

        $html .= "<tr>"
               . "<td>" . htmlspecialchars($r['pkg']) . "</td>"
               . "<td class='mono'>" . htmlspecialchars($r['view']) . "</td>"
               . "<td>" . htmlspecialchars($r['type']) . "</td>"
               . "<td>{$r['o_count']}</td>"
               . "<td>{$r['h_count']}</td>"
               . "<td>" . count($r['missing']) . "</td>"
               . "<td>" . count($r['extra']) . "</td>"
               . "<td class='field-list'>{$detail}</td>"
               . "</tr>\n";
    }

    $html .= <<<HTML
</tbody></table>

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

    file_put_contents($htmlFile, $html);
    echo "\nHTML report written to: $htmlFile\n";
}

echo "\n# END OF FIELD PARITY CHECK\n";
