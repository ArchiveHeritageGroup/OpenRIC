<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/dacs-manage')->middleware(['web', 'auth'])->group(function () {
    Route::match(['get', 'post'], '/edit/{slug}', [\OpenRicDacsManage\Controllers\DacsManageController::class, 'edit'])->name('openricdacsmanage.edit');
});
