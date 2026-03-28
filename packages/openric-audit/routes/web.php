<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Audit\Http\Controllers\AuditController;

Route::get('/', [AuditController::class, 'browse'])->name('audit.browse');
Route::get('/{id}', [AuditController::class, 'show'])->name('audit.show');
