<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Translation\Http\Controllers\TranslationController;

/*
|--------------------------------------------------------------------------
| Translation Admin Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio ahg-translation routes/web.php.
| All routes are prefixed with /admin/translation and require auth middleware.
|
| OpenRiC additions: reject, drafts listing, log, batch, field defaults.
|
*/

// ── Settings ────────────────────────────────────────────────────────────
Route::get('/settings', [TranslationController::class, 'settings'])
    ->name('openric.translation.settings');
Route::post('/settings', [TranslationController::class, 'settings']);

// ── Settings: field/language defaults ────────────────────────────────────
Route::post('/settings/fields', [TranslationController::class, 'saveFieldDefaults'])
    ->name('openric.translation.settings.fields');

// ── Translate form + AJAX ───────────────────────────────────────────────
Route::get('/translate/{entityIri}', [TranslationController::class, 'translate'])
    ->name('openric.translation.translate')
    ->where('entityIri', '.*');
Route::post('/translate/{entityIri}', [TranslationController::class, 'store'])
    ->name('openric.translation.store')
    ->where('entityIri', '.*');

// ── Draft actions ───────────────────────────────────────────────────────
Route::post('/apply', [TranslationController::class, 'apply'])
    ->name('openric.translation.apply');
Route::post('/reject', [TranslationController::class, 'reject'])
    ->name('openric.translation.reject');

// ── Batch translate ─────────────────────────────────────────────────────
Route::post('/batch', [TranslationController::class, 'batch'])
    ->name('openric.translation.batch');

// ── Health check ────────────────────────────────────────────────────────
Route::get('/health', [TranslationController::class, 'health'])
    ->name('openric.translation.health');

// ── Language management ─────────────────────────────────────────────────
Route::get('/languages', [TranslationController::class, 'languages'])
    ->name('openric.translation.languages');
Route::post('/languages', [TranslationController::class, 'addLanguage'])
    ->name('openric.translation.addLanguage');

// ── Drafts & log (JSON API) ────────────────────────────────────────────
Route::get('/drafts/{entityIri}', [TranslationController::class, 'drafts'])
    ->name('openric.translation.drafts')
    ->where('entityIri', '.*');
Route::get('/log/{entityIri}', [TranslationController::class, 'log'])
    ->name('openric.translation.log')
    ->where('entityIri', '.*');
