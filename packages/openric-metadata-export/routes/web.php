<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/metadata-export')->middleware(['web', 'auth'])->group(function () {
    Route::get('/bulk', [\OpenRicMetadataExport\Controllers\MetadataExportController::class, 'bulk'])->name('openricmetadataexport.bulk');
    Route::get('/index', [\OpenRicMetadataExport\Controllers\MetadataExportController::class, 'index'])->name('openricmetadataexport.index');
    Route::get('/preview', [\OpenRicMetadataExport\Controllers\MetadataExportController::class, 'preview'])->name('openricmetadataexport.preview');
});
