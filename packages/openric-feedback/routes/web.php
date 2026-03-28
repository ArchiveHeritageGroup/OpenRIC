<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Feedback\Http\Controllers\FeedbackController;

Route::get('/', [FeedbackController::class, 'index'])->name('feedback.index');
Route::get('/{id}', [FeedbackController::class, 'show'])->name('feedback.show')->whereNumber('id');
Route::post('/{id}/status', [FeedbackController::class, 'updateStatus'])->name('feedback.updateStatus')->whereNumber('id');
Route::get('/export', [FeedbackController::class, 'export'])->name('feedback.export');
