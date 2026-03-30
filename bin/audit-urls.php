#!/usr/bin/env php
<?php
/**
 * OpenRiC — Audit URL Scoping
 *
 * Checks that links on show/edit pages are record-scoped (contain $slug, $id,
 * etc.) rather than generic.  On a show or edit page every action link that
 * relates to the current record must include the record identifier so it
 * targets the right entity.
 *
 * Reports:
 *   - Total links, scoped vs generic, mismatches
 *   - List of mismatches (file, text, href, reason)
 *   - Top 30 generic link href patterns
 *
 * Usage:
 *   php bin/audit-urls.php
 *   php bin/audit-urls.php --verbose
 */

$base    = '/usr/share/nginx/OpenRiC/packages';
$verbose = in_array('--verbose', $argv);

echo "========================================\n";
echo "  OpenRiC — Audit URL Scoping\n";
echo "  Scanning: {$base}\n";
echo "========================================\n\n";

// ──────────────────────────────────────────────
// Link texts that MUST be record-scoped on show/edit pages
// ──────────────────────────────────────────────
$mustBeScopedLinks = [
    'Edit',
    'Delete',
    'Reports',
    'Browse as list',
    'Inventory',
    'Calculate dates',
    'Rename',
    'Manage rights',
    'Add new rights',
    'Create new rights',
    'Create rights',
    'Link physical storage',
    'More',
    'View',
    'Show',
    'Print',
    'Export',
    'Export CSV',
    'Export EAD',
    'Export DC',
    'Download',
    'Deaccession',
    'Add accrual',
    'Add event',
    'Add note',
    'Update',
    'Duplicate',
    'Move',
    'Link',
    'Unlink',
    'Condition report',
    'Add condition',
    'Digital object',
    'Upload',
    'Timeline',
    'Map',
    'IIIF manifest',
    'Cite',
    'Physical storage',
    'Rights',
    'Attachments',
    'Checklist',
    'Appraisal',
    'Valuation',
    'Containers',
    'Confirm delete',
    'Add alternative identifier',
    'Manage relationships',
    'Add relationship',
];

// Normalise for case-insensitive matching
$mustBeScopedNorm = [];
foreach ($mustBeScopedLinks as $text) {
    $mustBeScopedNorm[mb_strtolower(trim($text))] = $text;
}

// ──────────────────────────────────────────────
// Patterns that indicate a scoped href
// ──────────────────────────────────────────────
// If any of these appear in the href value the link is considered scoped
$scopedPatterns = [
    '->slug',
    '->id',
    '->getId()',
    '->getSlug()',
    '$slug',
    '$id',
    '$record',
    '$item',
    '$accession',
    '$actor',
    '$donor',
    '$repository',
    '$informationObject',
    '$object',
    '$entity',
    '$model',
    '$row',
    '$entry',
    '{{ $',
    '{!! $',
    // route() calls with a second argument are typically scoped
];

// ──────────────────────────────────────────────
// Identify show/edit blade files
// ──────────────────────────────────────────────
$showEditPatterns = [
    '/show\.blade\.php$/',
    '/edit\.blade\.php$/',
    '/view\.blade\.php$/',
    '/detail\.blade\.php$/',
    '/delete\.blade\.php$/',
    '/add\.blade\.php$/',
];

function isShowEditFile(string $filename): bool {
    global $showEditPatterns;
    foreach ($showEditPatterns as $pat) {
        if (preg_match($pat, $filename)) return true;
    }
    return false;
}

// ──────────────────────────────────────────────
// Check if an href is scoped
// ──────────────────────────────────────────────
function isScoped(string $rawHref): bool {
    global $scopedPatterns;

    foreach ($scopedPatterns as $pat) {
        if (str_contains($rawHref, $pat)) return true;
    }

    // route('name', $var) — second argument means scoped
    if (preg_match("/route\(\s*['\"][^'\"]+['\"]\s*,/", $rawHref)) {
        return true;
    }

    // url() with a variable concatenation
    if (preg_match("/url\(.+\\$/", $rawHref)) {
        return true;
    }

    return false;
}

// ──────────────────────────────────────────────
// Collect blade files
// ──────────────────────────────────────────────
$bladeFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
foreach ($rii as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $bladeFiles[] = $file->getPathname();
    }
}
sort($bladeFiles);

echo "  Found " . count($bladeFiles) . " blade files\n";

// ──────────────────────────────────────────────
// Analyse
// ──────────────────────────────────────────────
$totalLinks    = 0;
$totalScoped   = 0;
$totalGeneric  = 0;
$mismatches    = [];  // [file, text, href, reason]
$genericHrefs  = [];  // href => count

foreach ($bladeFiles as $filePath) {
    $relPath = str_replace($base . '/', '', $filePath);

    // Only audit show/edit/view/delete/add pages
    if (!isShowEditFile($filePath)) continue;

    $content = file_get_contents($filePath);
    if ($content === false) continue;

    // Extract <a ...>TEXT</a> with the full tag to get href and text
    // This handles multi-line anchor tags by collapsing whitespace
    $flat = preg_replace('/\s+/', ' ', $content);

    if (!preg_match_all('/<a\s([^>]*?)>(.*?)<\/a>/si', $flat, $matches, PREG_SET_ORDER)) {
        continue;
    }

    foreach ($matches as $m) {
        $attrs   = $m[1];
        $rawText = $m[2];

        // Extract href — handle Blade expressions {{ ... }} inside quotes
        // First try: href="...{{ ... }}..." (double-quoted with Blade)
        if (!preg_match('/href\s*=\s*"((?:[^"]*?\{\{.*?\}\}[^"]*?)*[^"]*?)"/i', $attrs, $hm)) {
            // Fallback: simple href="value" or href='value'
            if (!preg_match('/href\s*=\s*["\']([^"\']*?)["\']/i', $attrs, $hm)) continue;
        }

        $href = $hm[1];
        $totalLinks++;

        // Clean link text: strip tags, trim, normalise whitespace
        $linkText = trim(strip_tags($rawText));
        $linkText = preg_replace('/\s+/', ' ', $linkText);
        $linkTextLower = mb_strtolower($linkText);

        // Skip empty text, external, anchors, javascript
        if ($linkText === '') continue;
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) continue;
        if ($href === '#' || str_starts_with($href, '#')) continue;
        if (str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:')) continue;

        $scoped = isScoped($href);

        if ($scoped) {
            $totalScoped++;
        } else {
            $totalGeneric++;

            // Track generic href patterns
            $genericHrefs[$href] = ($genericHrefs[$href] ?? 0) + 1;

            // Check if this link text MUST be scoped
            if (isset($mustBeScopedNorm[$linkTextLower])) {
                $mismatches[] = [
                    'file'   => $relPath,
                    'text'   => $linkText,
                    'href'   => $href,
                    'reason' => "Link '{$linkText}' should be record-scoped on a show/edit page",
                ];
            }
        }
    }
}

// ──────────────────────────────────────────────
// Summary
// ──────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════╗\n";
echo "║           URL SCOPING AUDIT SUMMARY             ║\n";
echo "╠══════════════════════════════════════════════════╣\n";
printf("║  Total links (show/edit)  %'.-18d   ║\n", $totalLinks);
printf("║  Scoped (have \$id/\$slug) %'.-18d   ║\n", $totalScoped);
printf("║  Generic (no scope var)   %'.-18d   ║\n", $totalGeneric);
printf("║  MISMATCHES               %'.-18d   ║\n", count($mismatches));
echo "╚══════════════════════════════════════════════════╝\n";

// ──────────────────────────────────────────────
// Mismatches detail
// ──────────────────────────────────────────────
if (count($mismatches) > 0) {
    // Deduplicate
    $seen = [];
    $deduped = [];
    foreach ($mismatches as $mm) {
        $key = $mm['file'] . '|' . $mm['text'] . '|' . $mm['href'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $deduped[] = $mm;
        }
    }

    echo "\n=== MISMATCHES (" . count($deduped) . " unique) ===\n\n";

    // Group by file
    $byFile = [];
    foreach ($deduped as $mm) {
        $byFile[$mm['file']][] = $mm;
    }
    ksort($byFile);

    foreach ($byFile as $file => $items) {
        echo "  {$file}\n";
        foreach ($items as $mm) {
            echo "    MISMATCH  \"{$mm['text']}\"  href=\"{$mm['href']}\"\n";
            echo "              {$mm['reason']}\n";
        }
        echo "\n";
    }
} else {
    echo "\n  No scoping mismatches found.\n\n";
}

// ──────────────────────────────────────────────
// Top 30 generic href patterns
// ──────────────────────────────────────────────
if (count($genericHrefs) > 0) {
    arsort($genericHrefs);
    $top = array_slice($genericHrefs, 0, 30, true);

    echo "=== TOP " . count($top) . " GENERIC HREF PATTERNS ===\n\n";
    $rank = 0;
    foreach ($top as $href => $count) {
        $rank++;
        printf("  %2d. (%dx) %s\n", $rank, $count, $href);
    }
    echo "\n";
}

// ──────────────────────────────────────────────
// Verbose: all scoped links
// ──────────────────────────────────────────────
if ($verbose) {
    echo "=== SCOPED: {$totalScoped} | GENERIC: {$totalGeneric} ===\n";
    $pct = $totalLinks > 0 ? round($totalScoped / $totalLinks * 100, 1) : 0;
    echo "  Scoped percentage: {$pct}%\n\n";
}

// Exit code: 1 if mismatches found
exit(count($mismatches) > 0 ? 1 : 0);
