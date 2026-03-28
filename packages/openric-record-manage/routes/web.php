<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\RecordManage\Controllers\RecordController;
use OpenRiC\RecordManage\Controllers\RecordPartController;
use OpenRiC\RecordManage\Controllers\RecordSetController;

Route::resource('record-sets', RecordSetController::class)->parameters(['record-sets' => 'iri']);
Route::resource('records', RecordController::class)->parameters(['records' => 'iri']);
Route::resource('record-parts', RecordPartController::class)->parameters(['record-parts' => 'iri']);
