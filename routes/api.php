<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\RecordApiController;
use App\Http\Controllers\Api\V1\AgentApiController;
use App\Http\Controllers\Api\V1\EntityApiController;
use App\Http\Controllers\Api\V1\SparqlApiController;
use App\Http\Controllers\Api\V1\ExportApiController;

/**
 * OpenRiC REST API — /api/v1/
 *
 * Adapted from Heratio ahg-api pattern.
 * All responses are application/ld+json with RiC-O context.
 * Authentication: Laravel Sanctum (token-based).
 *
 * Public endpoints (no auth):
 *   GET  /api/v1/records           — Browse records
 *   GET  /api/v1/records/{iri}     — Get single record
 *   GET  /api/v1/agents            — Browse agents
 *   GET  /api/v1/agents/{iri}      — Get single agent
 *   GET  /api/v1/{entity}          — Browse any RiC-O entity type
 *   GET  /api/v1/{entity}/{iri}    — Get single entity
 *   GET  /api/v1/prefixes          — Get SPARQL prefix map
 *
 * Authenticated endpoints (Sanctum token required):
 *   POST   /api/v1/records         — Create record
 *   PUT    /api/v1/records/{iri}   — Update record
 *   DELETE /api/v1/records/{iri}   — Delete record
 *   POST   /api/v1/agents          — Create agent
 *   PUT    /api/v1/agents/{iri}    — Update agent
 *   DELETE /api/v1/agents/{iri}    — Delete agent
 *   POST   /api/v1/sparql          — Execute SPARQL query
 *   GET    /api/v1/{entity}/{iri}/export/{format} — Export entity
 */

// Public API — no authentication required
Route::prefix('v1')->group(function () {
    // Records
    Route::get('records', [RecordApiController::class, 'index']);
    Route::get('records/{iri}', [RecordApiController::class, 'show'])->where('iri', '.*');

    // Agents
    Route::get('agents', [AgentApiController::class, 'index']);
    Route::get('agents/{iri}', [AgentApiController::class, 'show'])->where('iri', '.*');

    // Generic entity endpoints (activities, places, dates, mandates, functions, instantiations)
    Route::get('{entityType}', [EntityApiController::class, 'index'])
        ->whereIn('entityType', ['activities', 'places', 'dates', 'mandates', 'functions', 'instantiations', 'record-sets', 'record-parts']);
    Route::get('{entityType}/{iri}', [EntityApiController::class, 'show'])
        ->whereIn('entityType', ['activities', 'places', 'dates', 'mandates', 'functions', 'instantiations', 'record-sets', 'record-parts'])
        ->where('iri', '.*');

    // SPARQL prefixes (public)
    Route::get('sparql/prefixes', [SparqlApiController::class, 'prefixes']);

    // Export (public, read-only)
    Route::get('{entityType}/{iri}/export/{format}', [ExportApiController::class, 'export'])
        ->where('iri', '.*')
        ->whereIn('format', ['jsonld', 'turtle', 'rdfxml', 'ntriples', 'ead3', 'eac-cpf', 'dc']);
});

// Authenticated API — Sanctum token required
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Records CRUD
    Route::post('records', [RecordApiController::class, 'store']);
    Route::put('records/{iri}', [RecordApiController::class, 'update'])->where('iri', '.*');
    Route::delete('records/{iri}', [RecordApiController::class, 'destroy'])->where('iri', '.*');

    // Agents CRUD
    Route::post('agents', [AgentApiController::class, 'store']);
    Route::put('agents/{iri}', [AgentApiController::class, 'update'])->where('iri', '.*');
    Route::delete('agents/{iri}', [AgentApiController::class, 'destroy'])->where('iri', '.*');

    // Generic entity CRUD
    Route::post('{entityType}', [EntityApiController::class, 'store'])
        ->whereIn('entityType', ['activities', 'places', 'dates', 'mandates', 'functions', 'instantiations', 'record-sets', 'record-parts']);
    Route::put('{entityType}/{iri}', [EntityApiController::class, 'update'])
        ->whereIn('entityType', ['activities', 'places', 'dates', 'mandates', 'functions', 'instantiations', 'record-sets', 'record-parts'])
        ->where('iri', '.*');
    Route::delete('{entityType}/{iri}', [EntityApiController::class, 'destroy'])
        ->whereIn('entityType', ['activities', 'places', 'dates', 'mandates', 'functions', 'instantiations', 'record-sets', 'record-parts'])
        ->where('iri', '.*');

    // SPARQL passthrough (authenticated only)
    Route::post('sparql', [SparqlApiController::class, 'query']);
});
