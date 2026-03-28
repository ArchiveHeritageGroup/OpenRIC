<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\AgentManage\Controllers\PersonController;
use OpenRiC\AgentManage\Controllers\CorporateBodyController;
use OpenRiC\AgentManage\Controllers\FamilyController;

Route::resource('persons', PersonController::class)->parameters(['persons' => 'iri']);
Route::resource('corporate-bodies', CorporateBodyController::class)->parameters(['corporate-bodies' => 'iri']);
Route::resource('families', FamilyController::class)->parameters(['families' => 'iri']);
