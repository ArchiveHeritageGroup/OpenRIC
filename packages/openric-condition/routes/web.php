<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Condition\Controllers\ConditionController;

Route::prefix('condition-assessments')->group(function () {
    Route::get('/', [ConditionController::class, 'index'])->name('condition.index');
    Route::get('/create', [ConditionController::class, 'create'])->name('condition.create');
    Route::post('/', [ConditionController::class, 'store'])->name('condition.store');
});
