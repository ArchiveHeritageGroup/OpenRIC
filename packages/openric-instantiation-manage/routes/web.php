<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\InstantiationManage\Controllers\InstantiationController;

Route::resource('instantiations', InstantiationController::class)->where(['iri' => '.*'])->parameters(['instantiations' => 'iri']);
