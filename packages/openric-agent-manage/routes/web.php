<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\AgentManage\Controllers\PersonController;
use OpenRiC\AgentManage\Controllers\CorporateBodyController;
use OpenRiC\AgentManage\Controllers\FamilyController;

Route::resource('persons', PersonController::class)->where(['iri' => '.*'])->parameters(['persons' => 'iri']);
Route::resource('corporate-bodies', CorporateBodyController::class)->where(['iri' => '.*'])->parameters(['corporate-bodies' => 'iri']);
Route::resource('families', FamilyController::class)->where(['iri' => '.*'])->parameters(['families' => 'iri']);
