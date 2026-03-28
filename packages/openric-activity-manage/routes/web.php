<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\ActivityManage\Controllers\ActivityController;
use OpenRiC\ActivityManage\Controllers\MandateController;
use OpenRiC\ActivityManage\Controllers\RiCFunctionController;

Route::resource('activities', ActivityController::class)->parameters(['activities' => 'iri']);
Route::resource('mandates', MandateController::class)->parameters(['mandates' => 'iri']);
Route::resource('functions', RiCFunctionController::class)->parameters(['functions' => 'iri']);
