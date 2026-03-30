<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/graphql')->middleware(['web', 'auth'])->group(function () {
    Route::get('/playground', [OpenRic\Graphql\Controllers\GraphqlController::class, 'playground'])->name('ahggraphql.playground');
    Route::post('/execute', [OpenRic\Graphql\Controllers\GraphqlController::class, 'execute'])->name('ahggraphql.execute');
});
