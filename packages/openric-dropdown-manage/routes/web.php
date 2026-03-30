<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DropdownManage\Http\Controllers\DropdownManageController;

// Legacy URL alias
Route::get('/admin/dropdown', fn () => redirect('/admin/dropdowns', 301));

Route::prefix('admin/dropdowns')->group(function (): void {
    Route::get('/', [DropdownManageController::class, 'index'])->name('dropdown.index');
    Route::get('/{taxonomy}/edit', [DropdownManageController::class, 'edit'])->name('dropdown.edit');

    // Taxonomy AJAX endpoints
    Route::post('/create', [DropdownManageController::class, 'createTaxonomy'])->name('dropdown.create');
    Route::post('/rename', [DropdownManageController::class, 'renameTaxonomy'])->name('dropdown.rename');
    Route::post('/delete-taxonomy', [DropdownManageController::class, 'deleteTaxonomy'])->name('dropdown.delete-taxonomy');
    Route::post('/move-section', [DropdownManageController::class, 'moveSection'])->name('dropdown.move-section');

    // Term AJAX endpoints
    Route::post('/add-term', [DropdownManageController::class, 'addTerm'])->name('dropdown.add-term');
    Route::post('/update-term', [DropdownManageController::class, 'updateTerm'])->name('dropdown.update-term');
    Route::post('/delete-term', [DropdownManageController::class, 'deleteTerm'])->name('dropdown.delete-term');
    Route::post('/reorder', [DropdownManageController::class, 'reorder'])->name('dropdown.reorder');
    Route::post('/set-default', [DropdownManageController::class, 'setDefault'])->name('dropdown.set-default');

    // Column mapping AJAX endpoints
    Route::get('/mappings', [DropdownManageController::class, 'mappings'])->name('dropdown.mappings');
    Route::post('/mappings', [DropdownManageController::class, 'createMapping'])->name('dropdown.mappings.create');
    Route::delete('/mappings/{id}', [DropdownManageController::class, 'destroyMapping'])->name('dropdown.mappings.destroy');
});
