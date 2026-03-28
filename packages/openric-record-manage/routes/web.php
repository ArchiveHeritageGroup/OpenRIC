<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\RecordManage\Controllers\FindingAidController;
use OpenRiC\RecordManage\Controllers\HierarchyController;
use OpenRiC\RecordManage\Controllers\RecordController;
use OpenRiC\RecordManage\Controllers\RecordPartController;
use OpenRiC\RecordManage\Controllers\RecordSetController;

Route::resource('record-sets', RecordSetController::class)->parameters(['record-sets' => 'iri']);
Route::resource('records', RecordController::class)->parameters(['records' => 'iri']);
Route::resource('record-parts', RecordPartController::class)->parameters(['record-parts' => 'iri']);

Route::get('/hierarchy', [HierarchyController::class, 'index'])->name('hierarchy.index');
Route::get('/hierarchy/children', [HierarchyController::class, 'children'])->name('hierarchy.children');
Route::get('/hierarchy/{iri}', [HierarchyController::class, 'tree'])->name('hierarchy.tree');

Route::get('/finding-aid/{iri}/print', [FindingAidController::class, 'print'])->name('finding-aid.print');
