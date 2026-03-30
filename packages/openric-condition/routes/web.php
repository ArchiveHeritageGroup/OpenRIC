<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Condition\Controllers\ConditionController;

Route::prefix('condition-assessments')->group(function () {
    Route::get('/', [ConditionController::class, 'index'])->name('condition.index');
    Route::get('/create', [ConditionController::class, 'create'])->name('condition.create');
    Route::post('/', [ConditionController::class, 'store'])->name('condition.store');
    Route::get('/{id}', [ConditionController::class, 'show'])->name('conditions.show')->where('id', '[0-9]+');
    Route::post('/{id}/photo', [ConditionController::class, 'uploadPhoto'])->name('condition.upload-photo')->where('id', '[0-9]+');
    Route::post('/photo/{id}/delete', [ConditionController::class, 'deletePhoto'])->name('condition.delete-photo')->where('id', '[0-9]+');
    Route::get('/photo/{photoId}/annotations', [ConditionController::class, 'getAnnotations'])->name('condition.annotations.get')->where('photoId', '[0-9]+');
    Route::post('/photo/{photoId}/annotations', [ConditionController::class, 'saveAnnotations'])->name('condition.annotations.save')->where('photoId', '[0-9]+');
    Route::get('/{id}/export', [ConditionController::class, 'exportReport'])->name('condition.export')->where('id', '[0-9]+');
    Route::get('/templates', [ConditionController::class, 'templates'])->name('condition.templates');
});

Route::prefix('condition-assessments')->middleware('admin')->group(function () {
    Route::get('/admin', [ConditionController::class, 'admin'])->name('condition.admin');
});
