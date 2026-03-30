<?php

use Illuminate\Support\Facades\Route;
use OpenRic\DcManage\Controllers\DcManageController;

Route::prefix('admin/dc-manage')->middleware(['web', 'auth'])->group(function () {
    Route::match(['get', 'post'], '/edit/{slug}', [DcManageController::class, 'edit'])->name('openricdcmanage.edit');
});
