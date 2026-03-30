#!/usr/bin/env php
<?php
/**
 * Route Extractor — bypass `artisan route:list` which hangs on large apps.
 *
 * Bootstraps Laravel, iterates the router directly, and outputs JSON.
 * Used by all audit scripts via: $routes = json_decode(shell_exec('php bin/lib/route-extract.php'), true);
 *
 * Usage:
 *   php bin/lib/route-extract.php                    # JSON array of all routes
 *   php bin/lib/route-extract.php --app /path/to/app # specify app root
 */

$appRoot = null;
$args = array_slice($argv ?? [], 1);
for ($i = 0; $i < count($args); $i++) {
    if ($args[$i] === '--app' && isset($args[$i + 1])) {
        $appRoot = $args[++$i];
    }
}

// Default: detect from script location (bin/lib/ → project root)
if (!$appRoot) {
    $appRoot = dirname(__DIR__, 2);
    // If that doesn't have artisan, try cwd
    if (!file_exists("$appRoot/artisan")) {
        $appRoot = getcwd();
    }
}

if (!file_exists("$appRoot/vendor/autoload.php") || !file_exists("$appRoot/bootstrap/app.php")) {
    fwrite(STDERR, "Cannot find Laravel app at: $appRoot\n");
    echo '[]';
    exit(1);
}

// Suppress warnings during bootstrap
error_reporting(E_ERROR | E_PARSE);

require "$appRoot/vendor/autoload.php";
$app = require "$appRoot/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$router = app('router');
$routes = $router->getRoutes();

$output = [];
foreach ($routes as $route) {
    $action = $route->getActionName();
    // Resolve action string without triggering class loading
    if ($action === 'Closure') {
        $actionStr = 'Closure';
    } else {
        $actionStr = $action;
    }

    $output[] = [
        'method' => implode('|', $route->methods()),
        'uri'    => $route->uri(),
        'name'   => $route->getName() ?? '',
        'action' => $actionStr,
    ];
}

echo json_encode($output);
