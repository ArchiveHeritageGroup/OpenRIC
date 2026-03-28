<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\ActivityManage\Controllers\ActivityController;
use OpenRiC\ActivityManage\Controllers\MandateController;
use OpenRiC\ActivityManage\Controllers\RiCFunctionController;

Route::resource('activities', ActivityController::class)->where(['iri' => '.*'])->parameters(['activities' => 'iri']);
Route::resource('mandates', MandateController::class)->where(['iri' => '.*'])->parameters(['mandates' => 'iri']);
Route::resource('functions', RiCFunctionController::class)->where(['iri' => '.*'])->parameters(['functions' => 'iri']);
