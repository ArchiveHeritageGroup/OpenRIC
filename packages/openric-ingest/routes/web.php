<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Ingest\Http\Controllers\IngestController;

Route::get('/', [IngestController::class, 'index'])->name('ingest.index');
Route::get('/csv', [IngestController::class, 'csvUpload'])->name('ingest.csv.upload');
Route::post('/csv', [IngestController::class, 'csvProcess'])->name('ingest.csv.process');
Route::get('/xml', [IngestController::class, 'xmlUpload'])->name('ingest.xml.upload');
Route::post('/xml', [IngestController::class, 'xmlProcess'])->name('ingest.xml.process');
Route::get('/history', [IngestController::class, 'history'])->name('ingest.history');
Route::get('/preview/{jobId}', [IngestController::class, 'preview'])->name('ingest.preview')->whereNumber('jobId');
