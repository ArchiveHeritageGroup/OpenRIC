<?php

use Illuminate\Support\Facades\Route;
use OpenRiC\SettingsManage\Http\Controllers\SettingsController;

// Dynamic theme CSS — public, no auth needed
Route::get('/css/openric-theme-dynamic.css', [SettingsController::class, 'dynamicCss'])->name('settings.dynamic-css')->withoutMiddleware('auth');

Route::prefix('admin/settings')->group(function () {
    // Dedicated settings pages (alphabetical, BEFORE catch-all)
    Route::match(['get', 'post'], '/clipboard', [SettingsController::class, 'clipboard'])->name('settings.clipboard');
    Route::match(['get', 'post'], '/csv-validator', [SettingsController::class, 'csvValidator'])->name('settings.csv-validator');
    Route::match(['get', 'post'], '/default-template', [SettingsController::class, 'defaultTemplate'])->name('settings.default-template');
    Route::match(['get', 'post'], '/diacritics', [SettingsController::class, 'diacritics'])->name('settings.diacritics');
    Route::match(['get', 'post'], '/digital-objects', [SettingsController::class, 'digitalObjects'])->name('settings.digital-objects');
    Route::match(['get', 'post'], '/dip-upload', [SettingsController::class, 'dipUpload'])->name('settings.dip-upload');
    Route::match(['get', 'post'], '/email', [SettingsController::class, 'email'])->name('settings.email');
    Route::match(['get', 'post'], '/finding-aid', [SettingsController::class, 'findingAid'])->name('settings.finding-aid');
    Route::match(['get', 'post'], '/global', [SettingsController::class, 'global'])->name('settings.global');
    Route::match(['get', 'post'], '/header-customizations', [SettingsController::class, 'headerCustomizations'])->name('settings.header-customizations');
    Route::match(['get', 'post'], '/identifier', [SettingsController::class, 'identifier'])->name('settings.identifier');
    Route::match(['get', 'post'], '/interface-labels', [SettingsController::class, 'interfaceLabels'])->name('settings.interface-labels');
    Route::match(['get', 'post'], '/inventory', [SettingsController::class, 'inventory'])->name('settings.inventory');
    Route::match(['get', 'post'], '/languages', [SettingsController::class, 'languages'])->name('settings.languages');
    Route::match(['get', 'post'], '/markdown', [SettingsController::class, 'markdown'])->name('settings.markdown');
    Route::match(['get', 'post'], '/oai', [SettingsController::class, 'oai'])->name('settings.oai');
    Route::match(['get', 'post'], '/permissions', [SettingsController::class, 'permissions'])->name('settings.permissions');
    Route::match(['get', 'post'], '/privacy-notification', [SettingsController::class, 'privacyNotification'])->name('settings.privacy-notification');
    Route::match(['get', 'post'], '/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::match(['get', 'post'], '/site-information', [SettingsController::class, 'siteInformation'])->name('settings.site-information');
    Route::match(['get', 'post'], '/storage-service', [SettingsController::class, 'storageService'])->name('settings.storage-service');
    Route::get('/system-info', [SettingsController::class, 'systemInfo'])->name('settings.system-info');
    Route::get('/services', [SettingsController::class, 'services'])->name('settings.services');
    Route::match(['get', 'post'], '/themes', [SettingsController::class, 'themes'])->name('settings.themes');
    Route::match(['get', 'post'], '/treeview', [SettingsController::class, 'treeview'])->name('settings.treeview');
    Route::match(['get', 'post'], '/uploads', [SettingsController::class, 'uploads'])->name('settings.uploads');
    Route::match(['get', 'post'], '/visible-elements', [SettingsController::class, 'visibleElements'])->name('settings.visible-elements');
    Route::match(['get', 'post'], '/web-analytics', [SettingsController::class, 'webAnalytics'])->name('settings.web-analytics');
    Route::match(['get', 'post'], '/ai-services', [SettingsController::class, 'aiServices'])->name('settings.ai-services');
    Route::match(['get', 'post'], '/ldap', [SettingsController::class, 'ldap'])->name('settings.ldap');
    Route::match(['get', 'post'], '/levels', [SettingsController::class, 'levels'])->name('settings.levels');
    Route::match(['get', 'post'], '/paths', [SettingsController::class, 'paths'])->name('settings.paths');
    Route::match(['get', 'post'], '/preservation', [SettingsController::class, 'preservation'])->name('settings.preservation');
    Route::match(['get', 'post'], '/webhooks', [SettingsController::class, 'webhooks'])->name('settings.webhooks');
    Route::match(['get', 'post'], '/tts', [SettingsController::class, 'tts'])->name('settings.tts');
    Route::match(['get', 'post'], '/icip-settings', [SettingsController::class, 'icipSettings'])->name('settings.icip-settings');
    Route::match(['get', 'post'], '/sector-numbering', [SettingsController::class, 'sectorNumbering'])->name('settings.sector-numbering');
    Route::match(['get', 'post'], '/numbering-schemes', [SettingsController::class, 'numberingSchemes'])->name('settings.numbering-schemes');
    Route::match(['get', 'post'], '/dam-tools', [SettingsController::class, 'damTools'])->name('settings.dam-tools');
    Route::match(['get', 'post'], '/page-elements', [SettingsController::class, 'pageElements'])->name('settings.page-elements');
    Route::match(['get', 'post'], '/error-log', [SettingsController::class, 'errorLog'])->name('settings.error-log');

    // OpenRiC group route must come before the catch-all {section} route
    Route::match(['get', 'post'], '/openric/{group}', [SettingsController::class, 'openricSection'])->name('settings.openric');
    Route::match(['get', 'post'], '/{section}', [SettingsController::class, 'section'])->name('settings.section');
    Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
});
