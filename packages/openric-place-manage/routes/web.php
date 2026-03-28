<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\PlaceManage\Controllers\PlaceController;

Route::resource('places', PlaceController::class)->where(['iri' => '.*'])->parameters(['places' => 'iri']);
