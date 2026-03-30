<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/mods-manage')->middleware(['web', 'auth'])->group(function () {
    Route::match(['get', 'post'], '/edit/{slug}', [\OpenRicModsManage\Controllers\ModsManageController::class, 'edit'])->name('openricmodsmanage.edit');
});
