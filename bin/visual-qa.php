#!/usr/bin/env php
<?php
/**
 * OpenRiC Visual QA — Structural Comparison Tool
 *
 * Fetches live pages from both OpenRiC and Heratio, extracts structural
 * elements (headings, links, buttons, forms, cards), and reports differences.
 *
 * Usage:
 *   php bin/visual-qa.php /path              Compare a single page
 *   php bin/visual-qa.php --all              Compare all predefined pages
 *   php bin/visual-qa.php --list             List predefined pages
 *   php bin/visual-qa.php --cookie=SESS=xxx  Provide session cookie for auth pages
 *
 * Examples:
 *   php bin/visual-qa.php /informationobject/browse
 *   php bin/visual-qa.php --all --cookie="PHPSESSID=abc123"
 */

$openricBase = 'https://ric.theahg.co.za';
$heratioBase = 'https://heratio.theahg.co.za';

// ── Predefined pages for --all mode ──
$predefinedPages = [
    '/informationobject/browse',
    '/actor/browse',
    '/repository/browse',
    '/accession/browse',
    '/taxonomy/browse',
    '/donor/browse',
    '/search',
    '/search/advanced',
    '/',
    '/about',
    '/user/login',
    '/settings',
    '/digitalobject/browse',
    '/physicalobject/browse',
    '/rightsHolder/browse',
    '/function/browse',
    '/clipboard',
    '/staticpage/browse',
    '/menu/browse',
    '/user/browse',
    '/report/browse',
];

// ── Parse arguments ──
$cookie    = '';
$pages     = [];
$runAll    = false;
$showList  = false;

foreach ($argv as $i => $arg) {
    if ($i === 0) continue; // script name

    if ($arg === '--all') {
        $runAll = true;
    } elseif ($arg === '--list') {
        $showList = true;
    } elseif (str_starts_with($arg, '--cookie=')) {
        $cookie = substr($arg, 9);
    } elseif (str_starts_with($arg, '--openric=')) {
        $openricBase = rtrim(substr($arg, 10), '/');
    } elseif (str_starts_with($arg, '--heratio=')) {
        $heratioBase = rtrim(substr($arg, 10), '/');
    } elseif (str_starts_with($arg, '/')) {
        $pages[] = $arg;
    }
}

if ($showList) {
    echo "Predefined pages:\n";
    foreach ($predefinedPages as $p) {
        echo "  {$p}\n";
    }
    exit(0);
}

if ($runAll) {
    $pages = $predefinedPages;
}

if (empty($pages)) {
    echo "Usage: php bin/visual-qa.php /path [--all] [--cookie=...]\n";
    echo "Run with --list to see predefined pages.\n";
    exit(1);
}

echo "============================================\n";
echo "  OpenRiC Visual QA\n";
echo "  OpenRiC: {$openricBase}\n";
echo "  Heratio: {$heratioBase}\n";
echo "  Pages:   " . count($pages) . "\n";
echo "============================================\n\n";

$summaryTable = [];

foreach ($pages as $page) {
    echo "──────────────────────────────────────────\n";
    echo "  Page: {$page}\n";
    echo "──────────────────────────────────────────\n";

    // Fetch HTML from both systems
    $openricHtml = fetchPage($openricBase . $page, $cookie);
    $heratioHtml = fetchPage($heratioBase . $page, $cookie);

    if ($openricHtml === false) {
        echo "  ERROR  Could not fetch OpenRiC page: {$openricBase}{$page}\n\n";
        $summaryTable[] = ['page' => $page, 'status' => 'FETCH_ERROR', 'missing' => 0, 'extra' => 0];
        continue;
    }
    if ($heratioHtml === false) {
        echo "  ERROR  Could not fetch Heratio page: {$heratioBase}{$page}\n\n";
        $summaryTable[] = ['page' => $page, 'status' => 'FETCH_ERROR', 'missing' => 0, 'extra' => 0];
        continue;
    }

    // Extract structural elements from both
    $openricElements = extractStructure($openricHtml);
    $heratioElements = extractStructure($heratioHtml);

    // Compare
    $totalMissing = 0;
    $totalExtra   = 0;

    foreach (['headings', 'links', 'buttons', 'cards', 'forms'] as $category) {
        $oSet = $openricElements[$category] ?? [];
        $hSet = $heratioElements[$category] ?? [];

        $missing = arrayDiffNormalized($hSet, $oSet); // In Heratio but not OpenRiC
        $extra   = arrayDiffNormalized($oSet, $hSet); // In OpenRiC but not Heratio

        if (!empty($missing) || !empty($extra)) {
            echo "\n  [{$category}]\n";

            foreach ($missing as $item) {
                echo "    MISSING  {$item}\n";
                $totalMissing++;
            }
            foreach ($extra as $item) {
                echo "    EXTRA    {$item}\n";
                $totalExtra++;
            }
        }
    }

    if ($totalMissing === 0 && $totalExtra === 0) {
        echo "  OK  No structural differences found.\n";
    }

    echo "\n  Summary: {$totalMissing} missing, {$totalExtra} extra\n\n";

    $summaryTable[] = [
        'page'    => $page,
        'status'  => ($totalMissing + $totalExtra === 0) ? 'PASS' : 'DIFF',
        'missing' => $totalMissing,
        'extra'   => $totalExtra,
    ];
}

// ── Final summary table ──
echo "\n============================================\n";
echo "  Summary Table\n";
echo "============================================\n";
echo str_pad('Page', 40) . str_pad('Status', 10) . str_pad('Missing', 10) . str_pad('Extra', 10) . "\n";
echo str_repeat('-', 70) . "\n";

$grandMissing = 0;
$grandExtra   = 0;
$passCount    = 0;
$failCount    = 0;

foreach ($summaryTable as $row) {
    echo str_pad($row['page'], 40)
       . str_pad($row['status'], 10)
       . str_pad($row['missing'], 10)
       . str_pad($row['extra'], 10) . "\n";
    $grandMissing += $row['missing'];
    $grandExtra   += $row['extra'];
    if ($row['status'] === 'PASS') $passCount++;
    else $failCount++;
}

echo str_repeat('-', 70) . "\n";
echo str_pad('TOTAL', 40)
   . str_pad("{$passCount}P/{$failCount}F", 10)
   . str_pad($grandMissing, 10)
   . str_pad($grandExtra, 10) . "\n";
echo "============================================\n";

// ─────────────────────────────────────────────
// Functions
// ─────────────────────────────────────────────

/**
 * Fetch a page via curl with optional cookie.
 * Returns HTML string or false on failure.
 */
function fetchPage(string $url, string $cookie = ''): string|false
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT      => 'OpenRiC-VisualQA/1.0',
    ]);

    if (!empty($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($html === false || !empty($error)) {
        echo "  CURL ERROR: {$error}\n";
        return false;
    }

    if ($httpCode >= 400) {
        echo "  HTTP {$httpCode} for {$url}\n";
        // Still return HTML for comparison (might be a styled error page)
    }

    return $html;
}

/**
 * Extract structural elements from HTML.
 *
 * Returns an associative array with:
 *   - headings: h1-h5 text content
 *   - links:    href + link text
 *   - buttons:  button/submit text
 *   - cards:    card-header / accordion-header text
 *   - forms:    form action + method
 */
function extractStructure(string $html): array
{
    $result = [
        'headings' => [],
        'links'    => [],
        'buttons'  => [],
        'cards'    => [],
        'forms'    => [],
    ];

    // Suppress libxml warnings for malformed HTML
    libxml_use_internal_errors(true);

    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // ── Headings (h1-h5) ──
    for ($level = 1; $level <= 5; $level++) {
        $nodes = $xpath->query("//h{$level}");
        foreach ($nodes as $node) {
            $text = normalizeText($node->textContent);
            if (!empty($text)) {
                $result['headings'][] = "h{$level}: {$text}";
            }
        }
    }

    // ── Links ──
    $links = $xpath->query('//a[@href]');
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        $text = normalizeText($link->textContent);

        // Skip empty anchors, javascript: links, and # links
        if (empty($href) || $href === '#' || str_starts_with($href, 'javascript:')) {
            continue;
        }

        // Normalize href: strip domain, keep path
        $parsed = parse_url($href);
        $path = $parsed['path'] ?? $href;
        if (!empty($text)) {
            $result['links'][] = "{$path} [{$text}]";
        } else {
            $result['links'][] = "{$path}";
        }
    }

    // ── Buttons and submits ──
    $buttons = $xpath->query('//button | //input[@type="submit"]');
    foreach ($buttons as $btn) {
        if ($btn->tagName === 'input') {
            $text = $btn->getAttribute('value') ?: 'Submit';
        } else {
            $text = normalizeText($btn->textContent);
        }
        if (!empty($text)) {
            $type = $btn->getAttribute('type') ?: 'button';
            $result['buttons'][] = "[{$type}] {$text}";
        }
    }

    // ── Card / accordion headers ──
    // Look for elements with card-header or accordion-header class
    $cardHeaders = $xpath->query('//*[contains(@class, "card-header") or contains(@class, "accordion-header") or contains(@class, "card-title")]');
    foreach ($cardHeaders as $ch) {
        $text = normalizeText($ch->textContent);
        if (!empty($text)) {
            $result['cards'][] = $text;
        }
    }

    // ── Forms ──
    $forms = $xpath->query('//form');
    foreach ($forms as $form) {
        $action = $form->getAttribute('action') ?: '(none)';
        $method = strtoupper($form->getAttribute('method') ?: 'GET');

        // Normalize action URL
        $parsed = parse_url($action);
        $path = $parsed['path'] ?? $action;

        $result['forms'][] = "{$method} {$path}";
    }

    // De-duplicate within each category
    foreach ($result as $key => $items) {
        $result[$key] = array_unique($items);
    }

    return $result;
}

/**
 * Normalize text: trim, collapse whitespace, remove icon unicode.
 */
function normalizeText(string $text): string
{
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    // Remove common icon characters (Font Awesome inserts these)
    $text = preg_replace('/[\x{F000}-\x{FFFF}]/u', '', $text);
    $text = trim($text);
    return $text;
}

/**
 * Compute array difference with normalized comparison.
 * Returns items in $a that are not in $b.
 */
function arrayDiffNormalized(array $a, array $b): array
{
    $bNormalized = array_map(fn($s) => strtolower(trim($s)), $b);
    $diff = [];

    foreach ($a as $item) {
        $normalized = strtolower(trim($item));
        if (!in_array($normalized, $bNormalized)) {
            $diff[] = $item;
        }
    }

    return $diff;
}
