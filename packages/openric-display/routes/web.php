<?php

declare(strict_types=1);

use OpenRiC\Display\Http\Controllers\DisplayController;
use Illuminate\Support\Facades\Route;

// Public browse routes
Route::match(['GET', 'POST'], '/display/browse', [DisplayController::class, 'browse'])->name('display.browse');
Route::match(['GET', 'POST'], '/display/browseAjax', [DisplayController::class, 'browseAjax'])->name('display.browse.ajax');
Route::get('/display/print', [DisplayController::class, 'printView'])->name('display.print');
Route::get('/display/exportCsv', [DisplayController::class, 'exportCsv'])->name('display.export.csv');
Route::get('/display/show/{id}', [DisplayController::class, 'show'])->name('display.show')->where('id', '[0-9]+');

// Override standard informationobject browse
Route::match(['GET', 'POST'], '/informationobject/browse', [DisplayController::class, 'browse'])->name('informationobject.browse.override');

// Admin routes (require auth)
Route::middleware('admin')->group(function () {
    Route::match(['GET', 'POST'], '/display', [DisplayController::class, 'index'])->name('display.index');
    Route::match(['GET', 'POST'], '/display/profiles', [DisplayController::class, 'profiles'])->name('display.profiles');
    Route::match(['GET', 'POST'], '/display/levels', [DisplayController::class, 'levels'])->name('display.levels');
    Route::match(['GET', 'POST'], '/display/fields', [DisplayController::class, 'fields'])->name('display.fields');
    Route::match(['GET', 'POST'], '/display/setType', [DisplayController::class, 'setType'])->name('display.set.type');
    Route::match(['GET', 'POST'], '/display/assignProfile', [DisplayController::class, 'assignProfile'])->name('display.assign.profile');
    Route::match(['GET', 'POST'], '/display/bulkSetType', [DisplayController::class, 'bulkSetType'])->name('display.bulk.set.type');
    Route::match(['GET', 'POST'], '/display/changeType', [DisplayController::class, 'changeType'])->name('display.change.type');
    Route::match(['GET', 'POST'], '/display/settings', [DisplayController::class, 'browseSettings'])->name('display.browse.settings');
    Route::post('/display/toggleGlamBrowse', [DisplayController::class, 'toggleGlamBrowse'])->name('display.toggle');
    Route::post('/display/saveBrowseSettings', [DisplayController::class, 'saveBrowseSettingsAjax'])->name('display.save.settings');
    Route::get('/display/getBrowseSettings', [DisplayController::class, 'getBrowseSettings'])->name('display.get.settings');
    Route::post('/display/resetBrowseSettings', [DisplayController::class, 'resetBrowseSettings'])->name('display.reset.settings');
    Route::get('/display/browse-embedded', [DisplayController::class, 'browseEmbedded'])->name('display.browse.embedded');
    Route::match(['GET', 'POST'], '/display/reindex', [DisplayController::class, 'reindex'])->name('display.reindex');
    Route::get('/display/search', [DisplayController::class, 'glamSearch'])->name('display.search');
    Route::get('/display/treeview', [DisplayController::class, 'treeviewPage'])->name('display.treeview');
});
