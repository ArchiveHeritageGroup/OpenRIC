<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/gis')->middleware(['web', 'auth'])->group(function () {
    Route::get('/bbox', [OpenRic\Gis\Controllers\GisController::class, 'bbox'])->name('ahggis.bbox');
    Route::get('/geojson', [OpenRic\Gis\Controllers\GisController::class, 'geojson'])->name('ahggis.geojson');
    Route::get('/radius', [OpenRic\Gis\Controllers\GisController::class, 'radius'])->name('ahggis.radius');
});
