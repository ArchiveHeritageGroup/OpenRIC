<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use OpenRiC\Theme\Controllers\HomeController;

Route::middleware('web')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    // Standalone Graph Explorer (no navbar)
    Route::get('/explorer', function () {
        return View::make('theme::explorer-standalone');
    })->name('explorer.standalone');
    
    // Language switcher — validates against lang/ directories on disk
    Route::get('/language/{locale}', function (string $locale) {
        if (is_dir(lang_path($locale))) {
            Session::put('locale', $locale);
            app()->setLocale($locale);
        }

        return redirect()->back();
    })->where('locale', '[a-z]{2}(_[A-Z]{2})?(@[a-z]+)?')->name('language.switch');
});
