<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Research\Controllers\AuditController;
use OpenRiC\Research\Controllers\ResearchController;

// =========================================================================
// Research Portal Routes
// =========================================================================

Route::prefix('research')->name('research.')->group(function () {

    // Dashboard
    Route::get('/', [ResearchController::class, 'index'])->name('index');
    Route::get('/dashboard', [ResearchController::class, 'dashboard'])->name('dashboard');

    // Registration
    Route::match(['get', 'post'], '/register', [ResearchController::class, 'register'])->name('register');
    Route::get('/registration-complete', [ResearchController::class, 'registrationComplete'])->name('registrationComplete');
    Route::match(['get', 'post'], '/public-register', [ResearchController::class, 'publicRegister'])->name('publicRegister');

    // Profile
    Route::match(['get', 'post'], '/profile', [ResearchController::class, 'profile'])->name('profile');

    // Renewal
    Route::match(['get', 'post'], '/renewal', [ResearchController::class, 'renewal'])->name('renewal');

    // Admin: Manage Researchers
    Route::get('/researchers', [ResearchController::class, 'researchers'])->name('researchers');
    Route::match(['get', 'post'], '/researchers/{id}', [ResearchController::class, 'viewResearcher'])->name('viewResearcher')->where('id', '[0-9]+');
    Route::post('/researchers/{id}/approve', [ResearchController::class, 'approveResearcher'])->name('approveResearcher');
    Route::post('/researchers/{id}/reject', [ResearchController::class, 'rejectResearcher'])->name('rejectResearcher');
    Route::post('/researchers/{id}/suspend', [ResearchController::class, 'suspendResearcher'])->name('suspendResearcher');

    // Bookings
    Route::get('/bookings', [ResearchController::class, 'bookings'])->name('bookings');
    Route::match(['get', 'post'], '/book', [ResearchController::class, 'book'])->name('book');
    Route::match(['get', 'post'], '/bookings/{id}', [ResearchController::class, 'viewBooking'])->name('viewBooking')->where('id', '[0-9]+');
    Route::post('/bookings/{id}/confirm', [ResearchController::class, 'confirmBooking'])->name('confirmBooking');
    Route::post('/bookings/{id}/check-in', [ResearchController::class, 'checkInBooking'])->name('checkInBooking');
    Route::post('/bookings/{id}/check-out', [ResearchController::class, 'checkOutBooking'])->name('checkOutBooking');
    Route::post('/bookings/{id}/no-show', [ResearchController::class, 'noShowBooking'])->name('noShowBooking');
    Route::post('/bookings/{id}/cancel', [ResearchController::class, 'cancelBooking'])->name('cancelBooking');

    // Workspace (personal)
    Route::match(['get', 'post'], '/workspace', [ResearchController::class, 'workspace'])->name('workspace');

    // Saved Searches
    Route::match(['get', 'post'], '/saved-searches', [ResearchController::class, 'savedSearches'])->name('savedSearches');
    Route::post('/saved-searches/store', [ResearchController::class, 'storeSavedSearch'])->name('storeSavedSearch');
    Route::get('/saved-searches/{id}/run', [ResearchController::class, 'runSavedSearch'])->name('runSavedSearch');
    Route::delete('/saved-searches/{id}', [ResearchController::class, 'destroySavedSearch'])->name('destroySavedSearch');

    // Collections
    Route::match(['get', 'post'], '/collections', [ResearchController::class, 'collections'])->name('collections');
    Route::match(['get', 'post'], '/collections/{id}', [ResearchController::class, 'viewCollection'])->name('viewCollection')->where('id', '[0-9]+');
    Route::post('/collections/store', [ResearchController::class, 'storeCollection'])->name('storeCollection');
    Route::delete('/collections/{id}', [ResearchController::class, 'destroyCollection'])->name('destroyCollection');

    // Annotations
    Route::match(['get', 'post'], '/annotations', [ResearchController::class, 'annotations'])->name('annotations');
    Route::post('/annotations/store', [ResearchController::class, 'storeAnnotation'])->name('storeAnnotation');
    Route::delete('/annotations/{id}', [ResearchController::class, 'destroyAnnotation'])->name('destroyAnnotation');

    // Journal
    Route::match(['get', 'post'], '/journal', [ResearchController::class, 'journal'])->name('journal');
    Route::match(['get', 'post'], '/journal/{id}', [ResearchController::class, 'journalEntry'])->name('journalEntry')->where('id', '[0-9]+');

    // Projects
    Route::match(['get', 'post'], '/projects', [ResearchController::class, 'projects'])->name('projects');
    Route::get('/projects/{id}', [ResearchController::class, 'viewProject'])->name('viewProject')->where('id', '[0-9]+');

    // Bibliographies
    Route::match(['get', 'post'], '/bibliographies', [ResearchController::class, 'bibliographies'])->name('bibliographies');
    Route::match(['get', 'post'], '/bibliographies/{id}', [ResearchController::class, 'viewBibliography'])->name('viewBibliography')->where('id', '[0-9]+');

    // Reports
    Route::get('/reports', [ResearchController::class, 'reports'])->name('reports');
    Route::match(['get', 'post'], '/reports/{id}', [ResearchController::class, 'viewReport'])->name('viewReport')->where('id', '[0-9]+');

    // Reproductions
    Route::get('/reproductions', [ResearchController::class, 'reproductions'])->name('reproductions');

    // Notifications
    Route::match(['get', 'post'], '/notifications', [ResearchController::class, 'notifications'])->name('notifications');

    // Admin: Reading Rooms
    Route::get('/rooms', [ResearchController::class, 'rooms'])->name('rooms');
    Route::match(['get', 'post'], '/rooms/edit', [ResearchController::class, 'editRoom'])->name('editRoom');

    // Admin: Seats, Equipment, Retrieval, Walk-In
    Route::get('/seats', [ResearchController::class, 'seats'])->name('seats');
    Route::get('/equipment', [ResearchController::class, 'equipment'])->name('equipment');
    Route::get('/retrieval-queue', [ResearchController::class, 'retrievalQueue'])->name('retrievalQueue');
    Route::match(['get', 'post'], '/walk-in', [ResearchController::class, 'walkIn'])->name('walkIn');

    // Admin: Types & Statistics
    Route::get('/admin/types', [ResearchController::class, 'adminTypes'])->name('adminTypes');
    Route::get('/admin/statistics', [ResearchController::class, 'adminStatistics'])->name('adminStatistics');
    Route::get('/admin/institutions', [ResearchController::class, 'institutions'])->name('institutions');
    Route::get('/admin/activities', [ResearchController::class, 'activities'])->name('activities');

    // API Keys
    Route::match(['get', 'post'], '/api-keys', [ResearchController::class, 'apiKeys'])->name('apiKeys');

    // Team Workspaces (CollaborationService)
    Route::match(['get', 'post'], '/workspaces', [ResearchController::class, 'workspaces'])->name('workspaces');

    // Validation Queue
    Route::get('/validation-queue', [ResearchController::class, 'validationQueue'])->name('validationQueue');
    Route::post('/validation-queue/{resultId}', [ResearchController::class, 'validateResult'])->name('validateResult');
    Route::post('/validation-queue/bulk', [ResearchController::class, 'bulkValidate'])->name('bulkValidate');

    // Entity Resolution
    Route::match(['get', 'post'], '/entity-resolution', [ResearchController::class, 'entityResolution'])->name('entityResolution');
    Route::post('/entity-resolution/{id}/resolve', [ResearchController::class, 'resolveEntityResolution'])->name('resolveEntityResolution');
    Route::get('/entity-resolution/{id}/conflicts', [ResearchController::class, 'entityResolutionConflicts'])->name('entityResolutionConflicts');

    // ODRL Policies
    Route::match(['get', 'post'], '/odrl-policies', [ResearchController::class, 'odrlPolicies'])->name('odrlPolicies');

    // Document Templates
    Route::match(['get', 'post'], '/document-templates', [ResearchController::class, 'documentTemplates'])->name('documentTemplates');

    // AJAX endpoints
    Route::get('/ajax/search-items', [ResearchController::class, 'searchItems'])->name('searchItems');
    Route::post('/ajax/add-to-collection', [ResearchController::class, 'addToCollection'])->name('addToCollection');
    Route::post('/ajax/create-collection', [ResearchController::class, 'createCollectionAjax'])->name('createCollectionAjax');
});

// Audit Routes (admin only)
Route::prefix('audit')->name('audit.')->group(function () {
    Route::get('/', [AuditController::class, 'index'])->name('index');
    Route::get('/{id}', [AuditController::class, 'view'])->name('view')->where('id', '[0-9]+');
    Route::get('/record/{tableName}/{recordId}', [AuditController::class, 'record'])->name('record');
    Route::get('/user/{userId}', [AuditController::class, 'user'])->name('user');
});

// Researcher registration alias
Route::match(['get', 'post'], '/researcher/register', [ResearchController::class, 'register'])->name('researcher.register');
