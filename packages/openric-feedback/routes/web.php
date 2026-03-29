<?php

declare(strict_types=1);

/**
 * Admin feedback routes (prefixed with /admin/feedback by service provider).
 *
 * Adapted from Heratio ahg-feedback routes/web.php which defined both public
 * and admin routes in one file. OpenRiC splits public routes into the provider
 * and keeps only admin routes here. Heratio's legacy AtoM URL redirects are omitted.
 */

use Illuminate\Support\Facades\Route;
use OpenRiC\Feedback\Http\Controllers\FeedbackController;

// Browse all feedback (admin dashboard)
Route::get('/', [FeedbackController::class, 'browse'])->name('feedback.browse');

// View single feedback entry
Route::get('/{id}/view', [FeedbackController::class, 'view'])->name('feedback.view')->whereNumber('id');

// Edit feedback (admin form)
Route::get('/{id}/edit', [FeedbackController::class, 'edit'])->name('feedback.edit')->whereNumber('id');

// Update feedback (admin POST)
Route::post('/{id}/update', [FeedbackController::class, 'update'])->name('feedback.update')->whereNumber('id');

// Delete feedback (admin POST)
Route::post('/{id}/delete', [FeedbackController::class, 'destroy'])->name('feedback.destroy')->whereNumber('id');

// Export all feedback as CSV
Route::get('/export', [FeedbackController::class, 'export'])->name('feedback.export');
