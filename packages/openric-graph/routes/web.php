<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Graph\Controllers\GraphController;

Route::prefix('graph')->group(function () {
    Route::get('/overview', [GraphController::class, 'overview'])->name('graph.overview');
    Route::get('/agent-network', [GraphController::class, 'agentNetwork'])->name('graph.agent-network');
    Route::get('/timeline', [GraphController::class, 'timeline'])->name('graph.timeline');
    Route::get('/entity/{iri}', [GraphController::class, 'entity'])->name('graph.entity');
    Route::get('/entity/{iri}/json', [GraphController::class, 'entityJson'])->name('graph.entity.json');
});
