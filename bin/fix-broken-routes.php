#!/usr/bin/env php
<?php
/**
 * OpenRiC Broken Route Auto-Fixer
 *
 * For each route('name') call in blade views that doesn't resolve to a
 * registered Laravel route, this script appends a stub GET route to the
 * appropriate package's routes/web.php file.
 *
 * Steps:
 *   1. Load all registered routes via `php artisan route:list --json`
 *   2. Scan all blade files for route('name') references
 *   3. Identify unregistered route names
 *   4. Group by package
 *   5. Append stub routes to each package's routes/web.php
 *
 * Usage:
 *   php bin/fix-broken-routes.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);
$base   = '/usr/share/nginx/OpenRiC/packages';

echo "===========================================\n";
echo "  OpenRiC Broken Route Auto-Fixer\n";
echo "  Scanning: {$base}\n";
if ($dryRun) echo "  MODE: dry-run (no files will be modified)\n";
echo "===========================================\n\n";

// ── 1. Get registered routes ──
echo "  Loading registered routes via artisan...\n";
$artisanPath = '/usr/share/nginx/OpenRiC/artisan';
$routeJson = shell_exec("php " . __DIR__ . "/lib/route-extract.php --app /usr/share/nginx/OpenRiC 2>/dev/null");
$registeredRoutes = [];

if ($routeJson) {
    $routes = json_decode($routeJson, true);
    if (is_array($routes)) {
        foreach ($routes as $route) {
            if (!empty($route['name'])) {
                $registeredRoutes[$route['name']] = true;
            }
        }
    }
}

echo "  Registered routes found: " . count($registeredRoutes) . "\n\n";

// ── 2. Scan blade files for route() references ──
$bladeFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
foreach ($rii as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $bladeFiles[] = $file->getPathname();
    }
}
sort($bladeFiles);

// Collect all route('name') references grouped by package
$routeRefs = [];       // routeName => [file1, file2, ...]
$packageMap = [];      // routeName => packageDir

foreach ($bladeFiles as $filePath) {
    $content = file_get_contents($filePath);

    // Match route('name') and route("name") — single-argument form
    preg_match_all("/route\(\s*['\"]([^'\"]+)['\"]\s*[,)]/", $content, $matches);

    if (empty($matches[1])) {
        continue;
    }

    // Determine which package this file belongs to
    $relPath = str_replace($base . '/', '', $filePath);
    $parts = explode('/', $relPath);
    $packageDir = $parts[0] ?? '';

    foreach ($matches[1] as $routeName) {
        // Skip if route is registered
        if (isset($registeredRoutes[$routeName])) {
            continue;
        }

        if (!isset($routeRefs[$routeName])) {
            $routeRefs[$routeName] = [];
        }
        $routeRefs[$routeName][] = $filePath;

        // Map route to best-guess package (first occurrence wins)
        if (!isset($packageMap[$routeName])) {
            $packageMap[$routeName] = $packageDir;
        }
    }
}

echo "  Broken route references found: " . count($routeRefs) . "\n\n";

if (empty($routeRefs)) {
    echo "  No broken routes to fix.\n";
    exit(0);
}

// ── 3. Group by package ──
$byPackage = [];
foreach ($routeRefs as $routeName => $files) {
    $pkg = $packageMap[$routeName];
    if (!isset($byPackage[$pkg])) {
        $byPackage[$pkg] = [];
    }
    $byPackage[$pkg][$routeName] = $files;
}

// ── 4. Generate and append stub routes ──
$totalRoutesAdded = 0;
$totalFilesCreated = 0;
$totalFilesUpdated = 0;

foreach ($byPackage as $packageDir => $routes) {
    $routeFile = "{$base}/{$packageDir}/routes/web.php";
    $routeDir  = dirname($routeFile);

    echo "  Package: {$packageDir}\n";
    echo "    Routes to add: " . count($routes) . "\n";

    // Build stub route block
    $stubs = [];
    $stubs[] = '';
    $stubs[] = '// ── Auto-generated stub routes (fix-broken-routes.php) ──';

    foreach ($routes as $routeName => $files) {
        $uri = routeNameToUri($routeName);
        $stubs[] = "Route::get('{$uri}', function () { return view('openric-core::error404'); })->name('{$routeName}');";
        $totalRoutesAdded++;

        $refCount = count(array_unique($files));
        echo "      + {$routeName}  (referenced in {$refCount} file(s))\n";
    }

    $stubBlock = implode("\n", $stubs) . "\n";

    if ($dryRun) {
        echo "    [dry-run] Would append " . count($routes) . " routes to {$routeFile}\n\n";
        continue;
    }

    // Create routes directory if needed
    if (!is_dir($routeDir)) {
        mkdir($routeDir, 0755, true);
    }

    // Create or append to routes/web.php
    if (!file_exists($routeFile)) {
        $header = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n";
        file_put_contents($routeFile, $header . $stubBlock);
        $totalFilesCreated++;
        echo "    CREATED {$routeFile}\n\n";
    } else {
        $existing = file_get_contents($routeFile);

        // Don't add routes that already exist in the file
        $toAdd = [];
        foreach ($routes as $routeName => $files) {
            if (!str_contains($existing, "->name('{$routeName}')") && !str_contains($existing, "->name(\"{$routeName}\")")) {
                $toAdd[] = $routeName;
            }
        }

        if (!empty($toAdd)) {
            $filteredStubs = [];
            $filteredStubs[] = '';
            $filteredStubs[] = '// ── Auto-generated stub routes (fix-broken-routes.php) ──';
            foreach ($toAdd as $routeName) {
                $uri = routeNameToUri($routeName);
                $filteredStubs[] = "Route::get('{$uri}', function () { return view('openric-core::error404'); })->name('{$routeName}');";
            }
            $filteredBlock = implode("\n", $filteredStubs) . "\n";
            file_put_contents($routeFile, $existing . $filteredBlock);
            $totalFilesUpdated++;
            echo "    UPDATED {$routeFile}\n\n";
        } else {
            echo "    (all routes already defined)\n\n";
        }
    }
}

echo "===========================================\n";
echo "  Summary\n";
echo "  Stub routes added:  {$totalRoutesAdded}\n";
echo "  Files created:      {$totalFilesCreated}\n";
echo "  Files updated:      {$totalFilesUpdated}\n";
echo "===========================================\n";

// ─────────────────────────────────────────────
// Helper functions
// ─────────────────────────────────────────────

/**
 * Convert a dot-notation route name to a URI path.
 *
 * Examples:
 *   'accession.browse'           -> '/accession/browse'
 *   'settings.global'            -> '/settings/global'
 *   'informationobject.edit'     -> '/informationobject/edit'
 *   'admin.taxonomy.index'       -> '/admin/taxonomy/index'
 */
function routeNameToUri(string $routeName): string
{
    $segments = explode('.', $routeName);
    $uri = '/' . implode('/', $segments);

    // If last segment is 'index', keep it but also make it clear
    // If last segment implies a parameter (show, edit, delete), add {id}
    $last = end($segments);
    if (in_array($last, ['show', 'edit', 'delete', 'update', 'destroy'])) {
        $uri .= '/{id}';
    }

    return $uri;
}
