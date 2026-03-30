#!/usr/bin/env php
<?php
/**
 * OpenRiC Route & Control Audit
 * Adapted from Heratio bin/audit-controls.php and bin/audit-urls-v2.php
 *
 * Tests every route, link, button, and form control on the live site.
 * Uses curl to fetch pages and parses HTML for controls.
 *
 * Usage: php bin/audit-routes.php [--base-url=https://ric.theahg.co.za] [--cookie=session_cookie]
 */

$baseUrl = 'https://ric.theahg.co.za';
$cookie = '';

// Parse args
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base-url=')) $baseUrl = substr($arg, 11);
    if (str_starts_with($arg, '--cookie=')) $cookie = substr($arg, 9);
}

$baseUrl = rtrim($baseUrl, '/');

// Get all routes from artisan
$routeOutput = shell_exec('php ' . __DIR__ . '/lib/route-extract.php --app /usr/share/nginx/OpenRiC 2>/dev/null');
$routes = json_decode($routeOutput ?: '[]', true);

$results = [];
$totalPass = 0;
$totalFail = 0;
$totalSkip = 0;

echo "========================================\n";
echo "  OpenRiC Route & Control Audit\n";
echo "  Base URL: {$baseUrl}\n";
echo "  Routes: " . count($routes) . "\n";
echo "========================================\n\n";

foreach ($routes as $route) {
    $method = $route['method'] ?? 'GET';
    $uri = $route['uri'] ?? '';
    $name = $route['name'] ?? '';
    $action = $route['action'] ?? '';

    // Only test GET routes
    if (!str_contains($method, 'GET')) continue;

    // Skip routes with parameters we can't fill
    if (preg_match('/\{[^}]+\}/', $uri)) {
        echo "  SKIP  {$method} /{$uri} (has parameters)\n";
        $totalSkip++;
        $results[] = ['status' => 'SKIP', 'method' => $method, 'uri' => $uri, 'name' => $name, 'http' => '-', 'controls' => []];
        continue;
    }

    $url = "{$baseUrl}/{$uri}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    // Count controls
    $controls = countControls($html ?: '');

    $status = ($httpCode >= 200 && $httpCode < 400) ? 'PASS' : 'FAIL';
    if ($status === 'PASS') $totalPass++; else $totalFail++;

    $controlSummary = "L:{$controls['links']} B:{$controls['buttons']} I:{$controls['inputs']} S:{$controls['selects']} F:{$controls['forms']}";
    echo "  {$status}  {$httpCode}  /{$uri}  [{$controlSummary}]\n";

    $results[] = [
        'status' => $status,
        'method' => $method,
        'uri' => $uri,
        'name' => $name,
        'http' => $httpCode,
        'controls' => $controls,
        'links_detail' => $controls['link_hrefs'],
    ];
}

echo "\n========================================\n";
echo "  PASS: {$totalPass}  FAIL: {$totalFail}  SKIP: {$totalSkip}\n";
echo "  Total: " . ($totalPass + $totalFail + $totalSkip) . "\n";
echo "========================================\n";

// Write JSON report
$reportPath = '/usr/share/nginx/OpenRiC/storage/logs/audit-report-' . date('Y-m-d-His') . '.json';
file_put_contents($reportPath, json_encode($results, JSON_PRETTY_PRINT));
echo "\nReport saved: {$reportPath}\n";

// Summary of broken links found across all pages
$brokenLinks = [];
foreach ($results as $r) {
    foreach ($r['links_detail'] ?? [] as $href) {
        if (str_starts_with($href, 'http') || str_starts_with($href, '#') || str_starts_with($href, 'javascript')) continue;
        if (str_contains($href, '{{')) continue; // Blade expression
        $fullUrl = $baseUrl . '/' . ltrim($href, '/');
        // Quick check
        $ch2 = curl_init($fullUrl);
        curl_setopt($ch2, CURLOPT_NOBODY, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
        if ($cookie) curl_setopt($ch2, CURLOPT_COOKIE, $cookie);
        curl_exec($ch2);
        $linkCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        if ($linkCode >= 400) {
            $brokenLinks[] = ['page' => $r['uri'], 'href' => $href, 'http' => $linkCode];
        }
    }
}

if (count($brokenLinks) > 0) {
    echo "\n=== BROKEN LINKS ===\n";
    foreach ($brokenLinks as $bl) {
        echo "  {$bl['http']}  {$bl['href']}  (on /{$bl['page']})\n";
    }
}

function countControls(string $content): array {
    $c = [
        'buttons' => 0, 'links' => 0, 'inputs' => 0, 'selects' => 0,
        'textareas' => 0, 'forms' => 0, 'tables' => 0, 'link_hrefs' => [],
    ];
    $c['buttons'] = preg_match_all('/<button\b/i', $content);
    $c['links'] = preg_match_all('/<a\s[^>]*href\s*=/i', $content);
    $c['inputs'] = preg_match_all('/<input\b/i', $content);
    $c['selects'] = preg_match_all('/<select\b/i', $content);
    $c['textareas'] = preg_match_all('/<textarea\b/i', $content);
    $c['forms'] = preg_match_all('/<form\b/i', $content);
    $c['tables'] = preg_match_all('/<table\b/i', $content);

    if (preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']*?)["\']/i', $content, $m)) {
        $c['link_hrefs'] = array_unique($m[1]);
    }
    return $c;
}
