<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use OpenRiC\Triplestore\Exceptions\TriplestoreException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.required' => \App\Http\Middleware\Authenticate::class,
        ]);

        // Append locale + view-switch after session start in the web group
        $middleware->appendToGroup('web', [
            \OpenRiC\Theme\Http\Middleware\SetLocaleMiddleware::class,
            \OpenRiC\Theme\Http\Middleware\ViewSwitchMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (TriplestoreException $e) {
            return response()->view('errors.triplestore', [
                'message' => 'The triplestore (Fuseki) is temporarily unavailable. Please try again in a moment.',
                'detail' => $e->getMessage(),
            ], 503);
        });
    })->create();
