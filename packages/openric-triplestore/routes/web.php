<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Triplestore\Controllers\SparqlEndpointController;

// Public SPARQL endpoint — no auth required
Route::get('/sparql', [SparqlEndpointController::class, 'form'])->name('sparql.form');
Route::match(['get', 'post'], '/sparql/query', [SparqlEndpointController::class, 'query'])->name('sparql.query');
Route::get('/sparql/prefixes', [SparqlEndpointController::class, 'prefixes'])->name('sparql.prefixes');
