<?php

use Illuminate\Support\Facades\Route;
use OpenRic\Api\Controllers\V2\ApiRootController;
use OpenRic\Api\Controllers\V2\SearchController;
use OpenRic\Api\Controllers\V2\AuthorityController;
use OpenRic\Api\Controllers\V2\TaxonomyController;
use OpenRic\Api\Controllers\V2\AuditController;

/*
|--------------------------------------------------------------------------
| OpenRiC API v2 Routes
|--------------------------------------------------------------------------
|
| REST API endpoints for OpenRiC with RiC-O data model.
| All endpoints use the Triplestore for data storage.
|
*/

Route::prefix('v2')->middleware(['api.cors', 'api.log'])->group(function () {
    
    // Public endpoints
    Route::get('/', [ApiRootController::class, 'index']);
    Route::get('/health', [ApiRootController::class, 'health']);
    
    // Search endpoints
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/suggest', [SearchController::class, 'suggest']);
    
    // Public authority/data endpoints (read-only)
    Route::middleware('api.ratelimit:200,60')->group(function () {
        Route::get('/records', [AuthorityController::class, 'listRecords']);
        Route::get('/records/{iri}', [AuthorityController::class, 'getRecord']);
        Route::get('/agents', [AuthorityController::class, 'listAgents']);
        Route::get('/agents/{iri}', [AuthorityController::class, 'getAgent']);
        Route::get('/activities', [AuthorityController::class, 'listActivities']);
        Route::get('/activities/{iri}', [AuthorityController::class, 'getActivity']);
        Route::get('/places', [AuthorityController::class, 'listPlaces']);
        Route::get('/places/{iri}', [AuthorityController::class, 'getPlace']);
        
        // Taxonomy terms
        Route::get('/taxonomies', [TaxonomyController::class, 'index']);
        Route::get('/taxonomies/{id}', [TaxonomyController::class, 'show']);
        Route::get('/taxonomies/{id}/terms', [TaxonomyController::class, 'terms']);
    });
    
    // Authenticated endpoints
    Route::middleware(['api.auth', 'api.ratelimit:500,60'])->group(function () {
        
        // CRUD operations
        Route::post('/records', [AuthorityController::class, 'createRecord']);
        Route::put('/records/{iri}', [AuthorityController::class, 'updateRecord']);
        Route::delete('/records/{iri}', [AuthorityController::class, 'deleteRecord']);
        
        Route::post('/agents', [AuthorityController::class, 'createAgent']);
        Route::put('/agents/{iri}', [AuthorityController::class, 'updateAgent']);
        Route::delete('/agents/{iri}', [AuthorityController::class, 'deleteAgent']);
        
        Route::post('/activities', [AuthorityController::class, 'createActivity']);
        Route::put('/activities/{iri}', [AuthorityController::class, 'updateActivity']);
        Route::delete('/activities/{iri}', [AuthorityController::class, 'deleteActivity']);
        
        // Relationships
        Route::post('/relationships', [AuthorityController::class, 'createRelationship']);
        Route::delete('/relationships', [AuthorityController::class, 'deleteRelationship']);
        
        // Taxonomy management
        Route::post('/taxonomies', [TaxonomyController::class, 'store']);
        Route::put('/taxonomies/{id}', [TaxonomyController::class, 'update']);
        Route::delete('/taxonomies/{id}', [TaxonomyController::class, 'destroy']);
        Route::post('/taxonomies/{id}/terms', [TaxonomyController::class, 'addTerm']);
        Route::delete('/taxonomies/{taxonomyId}/terms/{termId}', [TaxonomyController::class, 'removeTerm']);
        
        // Audit log (requires audit scope)
        Route::middleware('api.auth:audit')->group(function () {
            Route::get('/audit', [AuditController::class, 'index']);
            Route::get('/audit/{iri}', [AuditController::class, 'show']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| OpenRiC API v1 Routes (Legacy)
|--------------------------------------------------------------------------
| Deprecated - maintained for backward compatibility
*/

Route::prefix('v1')->middleware(['api.cors', 'api.log', 'api.ratelimit:100,60'])->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));
    
    // Legacy endpoints would be mapped here
    // These are deprecated and should use v2
});
