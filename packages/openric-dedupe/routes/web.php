<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Dedupe\Controllers\DedupeController;

/*
|--------------------------------------------------------------------------
| Dedupe Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio dedupe routes (46 lines). Expanded to full parity
| with all Heratio actions: dashboard, browse, compare, dismiss, merge,
| rules CRUD, scan, report, authority views, and real-time API.
|
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('dedupe')->name('dedupe.')->group(function () {
    // Dashboard
    Route::get('/', [DedupeController::class, 'dashboard'])->name('dashboard');

    // Browse duplicates
    Route::get('/records', [DedupeController::class, 'records'])->name('records');
    Route::get('/agents', [DedupeController::class, 'agents'])->name('agents');

    // Compare and merge
    Route::get('/compare/{id}', [DedupeController::class, 'compare'])->name('compare')->whereNumber('id');
    Route::match(['GET', 'POST'], '/merge/{id}', [DedupeController::class, 'merge'])->name('merge')->whereNumber('id');
    Route::post('/resolve/{id}', [DedupeController::class, 'resolve'])->name('resolve')->whereNumber('id');

    // Scan
    Route::get('/scan', [DedupeController::class, 'scan'])->name('scan');
    Route::post('/scan', [DedupeController::class, 'scanStart'])->name('scan.start');

    // Rules CRUD
    Route::get('/rules', [DedupeController::class, 'rules'])->name('rules');
    Route::get('/rule/create', [DedupeController::class, 'ruleCreate'])->name('rule.create');
    Route::post('/rule/create', [DedupeController::class, 'ruleStore'])->name('rule.store');
    Route::get('/rule/{id}/edit', [DedupeController::class, 'ruleEdit'])->name('rule.edit')->whereNumber('id');
    Route::post('/rule/{id}/edit', [DedupeController::class, 'ruleUpdate'])->name('rule.update')->whereNumber('id');
    Route::get('/rule/{id}/delete', [DedupeController::class, 'ruleDelete'])->name('rule.delete')->whereNumber('id');

    // Report
    Route::get('/report', [DedupeController::class, 'report'])->name('report');

    // Authority-related views
    Route::match(['get', 'post'], '/config', [DedupeController::class, 'config'])->name('config');
    Route::get('/contact/{id}', [DedupeController::class, 'contact'])->name('contact')->whereNumber('id');
    Route::get('/authority-dashboard', [DedupeController::class, 'authorityDashboard'])->name('authority-dashboard');
    Route::get('/function-browse', [DedupeController::class, 'functionBrowse'])->name('function-browse');
    Route::get('/functions/{id}', [DedupeController::class, 'functions'])->name('functions')->whereNumber('id');
    Route::get('/identifiers', [DedupeController::class, 'identifiers'])->name('identifiers');
    Route::get('/occupations', [DedupeController::class, 'occupations'])->name('occupations');
    Route::match(['get', 'post'], '/split/{id}', [DedupeController::class, 'split'])->name('split')->whereNumber('id');
    Route::get('/workqueue', [DedupeController::class, 'workqueue'])->name('workqueue');
});

// API: Real-time duplicate check (AJAX, used by JS widgets during data entry)
Route::middleware('auth')->get('/api/dedupe/realtime', [DedupeController::class, 'apiRealtime'])->name('dedupe.api.realtime');
