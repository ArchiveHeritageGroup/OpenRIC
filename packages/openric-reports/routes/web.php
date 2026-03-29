<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Reports\Controllers\ReportController;
use OpenRiC\Reports\Controllers\ReportBuilderController;

// ── Main dashboard (auth only) ──────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::get('/reports', [ReportController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('/reports/index', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/admin/reports', [ReportController::class, 'dashboard']); // legacy alias
});

// ── Admin report routes ─────────────────────────────────────────────
Route::middleware('admin')->prefix('admin/reports')->name('reports.')->group(function (): void {

    // Entity reports with CSV export
    Route::get('/descriptions', [ReportController::class, 'descriptions'])->name('descriptions');
    Route::get('/agents', [ReportController::class, 'agents'])->name('agents');
    Route::get('/repositories', [ReportController::class, 'repositories'])->name('repositories');
    Route::get('/accessions', [ReportController::class, 'accessions'])->name('accessions');
    Route::get('/donors', [ReportController::class, 'donors'])->name('donors');
    Route::get('/storage', [ReportController::class, 'storage'])->name('storage');
    Route::get('/taxonomy', [ReportController::class, 'taxonomy'])->name('taxonomy');
    Route::get('/recent', [ReportController::class, 'recent'])->name('recent');
    Route::get('/activity', [ReportController::class, 'activity'])->name('activity');
    Route::get('/access', [ReportController::class, 'access'])->name('access');
    Route::get('/collections', [ReportController::class, 'collections'])->name('collections');
    Route::get('/search', [ReportController::class, 'search'])->name('search');
    Route::match(['get', 'post'], '/spatial-analysis', [ReportController::class, 'spatialAnalysis'])->name('spatial');

    // Browse & Publish
    Route::get('/browse', [ReportController::class, 'browse'])->name('browse');
    Route::match(['get', 'post'], '/browse-publish', [ReportController::class, 'browsePublish'])->name('browse-publish');

    // Report Select & Generic Report
    Route::get('/select', [ReportController::class, 'reportSelect'])->name('select');
    Route::get('/report', [ReportController::class, 'report'])->name('report');

    // Generic export
    Route::get('/export', [ReportController::class, 'export'])->name('export');

    // Audit reports
    Route::get('/audit/agents', [ReportController::class, 'auditAgents'])->name('audit.agents');
    Route::get('/audit/descriptions', [ReportController::class, 'auditDescriptions'])->name('audit.descriptions');
    Route::get('/audit/donors', [ReportController::class, 'auditDonors'])->name('audit.donors');
    Route::get('/audit/permissions', [ReportController::class, 'auditPermissions'])->name('audit.permissions');
    Route::get('/audit/physical-storage', [ReportController::class, 'auditPhysicalStorage'])->name('audit.physical-storage');
    Route::get('/audit/repositories', [ReportController::class, 'auditRepositories'])->name('audit.repositories');
    Route::get('/audit/taxonomies', [ReportController::class, 'auditTaxonomies'])->name('audit.taxonomies');

    // ── Report Builder ──────────────────────────────────────────────
    Route::prefix('builder')->group(function (): void {
        Route::get('/', [ReportBuilderController::class, 'index'])->name('builder.index');
        Route::get('/create', [ReportBuilderController::class, 'create'])->name('builder.create');
        Route::post('/store', [ReportBuilderController::class, 'store'])->name('builder.store');
        Route::get('/templates', [ReportBuilderController::class, 'templates'])->name('builder.templates');
        Route::get('/archive', [ReportBuilderController::class, 'archive'])->name('builder.archive');
        Route::get('/{id}/preview', [ReportBuilderController::class, 'preview'])->name('builder.preview');
        Route::get('/{id}/edit', [ReportBuilderController::class, 'edit'])->name('builder.edit');
        Route::put('/{id}', [ReportBuilderController::class, 'update'])->name('builder.update');
        Route::get('/{id}/view', [ReportBuilderController::class, 'view'])->name('builder.view');
        Route::get('/{id}/query', [ReportBuilderController::class, 'query'])->name('builder.query');
        Route::get('/{id}/schedule', [ReportBuilderController::class, 'schedule'])->name('builder.schedule');
        Route::post('/{id}/schedule', [ReportBuilderController::class, 'scheduleStore'])->name('builder.schedule-store');
        Route::get('/{id}/share', [ReportBuilderController::class, 'share'])->name('builder.share');
        Route::get('/{id}/history', [ReportBuilderController::class, 'history'])->name('builder.history');
        Route::get('/{id}/widget', [ReportBuilderController::class, 'widget'])->name('builder.widget');
        Route::get('/{id}/export/{format}', [ReportBuilderController::class, 'export'])->name('builder.export');
        Route::get('/{id}/clone', [ReportBuilderController::class, 'cloneReport'])->name('builder.clone');
        Route::post('/{id}/delete', [ReportBuilderController::class, 'apiDelete'])->name('builder.delete');
        Route::get('/template/{id}/edit', [ReportBuilderController::class, 'editTemplate'])->name('builder.edit-template');
        Route::get('/template/{id}/preview', [ReportBuilderController::class, 'previewTemplate'])->name('builder.preview-template');
        Route::delete('/template/{id}', [ReportBuilderController::class, 'deleteTemplate'])->name('builder.delete-template');
    });
});

// ── Public custom report view ───────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::get('/reports/custom/{id}', [ReportBuilderController::class, 'view'])
        ->name('reports.custom.view')
        ->where('id', '[0-9]+');
});

// ── Report Builder API routes ───────────────────────────────────────
Route::middleware('admin')->group(function (): void {
    Route::post('/api/report-builder/save', [ReportBuilderController::class, 'apiSave'])->name('reports.api.save');
    Route::post('/api/report-builder/data/{id}', [ReportBuilderController::class, 'apiData'])->name('reports.api.data');
    Route::get('/api/report-builder/columns', [ReportBuilderController::class, 'apiColumns'])->name('reports.api.columns');
    Route::get('/api/report-builder/tables', [ReportBuilderController::class, 'apiQueryTables'])->name('reports.api.tables');
    Route::post('/api/report-builder/query/validate', [ReportBuilderController::class, 'apiQueryValidate'])->name('reports.api.query.validate');
    Route::post('/api/report-builder/query/execute', [ReportBuilderController::class, 'apiQueryExecute'])->name('reports.api.query.execute');
    Route::get('/api/report-builder/query/relationships', [ReportBuilderController::class, 'apiQueryRelationships'])->name('reports.api.query.relationships');
    Route::post('/api/report-builder/{id}/query/save', [ReportBuilderController::class, 'apiQuerySave'])->name('reports.api.query.save');
    Route::post('/api/report-builder/{id}/status', [ReportBuilderController::class, 'apiStatusChange'])->name('reports.api.status');
    Route::get('/api/report-builder/{id}/chart-data', [ReportBuilderController::class, 'apiChartData'])->name('reports.api.chart-data');
    Route::get('/api/report-builder/entity-search', [ReportBuilderController::class, 'apiEntitySearch'])->name('reports.api.entity-search');
    Route::post('/api/report-builder/{id}/section', [ReportBuilderController::class, 'apiSectionSave'])->name('reports.api.section.save');
    Route::delete('/api/report-builder/{id}/section/{sectionId}', [ReportBuilderController::class, 'apiSectionDelete'])->name('reports.api.section.delete');
    Route::post('/api/report-builder/{id}/section/reorder', [ReportBuilderController::class, 'apiSectionReorder'])->name('reports.api.section.reorder');
    Route::post('/api/report-builder/{id}/snapshot', [ReportBuilderController::class, 'apiSnapshot'])->name('reports.api.snapshot');
    Route::get('/api/report-builder/{id}/versions', [ReportBuilderController::class, 'apiVersions'])->name('reports.api.versions');
    Route::post('/api/report-builder/{id}/version', [ReportBuilderController::class, 'apiVersionCreate'])->name('reports.api.version.create');
    Route::post('/api/report-builder/{id}/version/{versionId}/restore', [ReportBuilderController::class, 'apiVersionRestore'])->name('reports.api.version.restore');
    Route::post('/api/report-builder/{id}/share', [ReportBuilderController::class, 'apiShareCreate'])->name('reports.api.share.create');
    Route::post('/api/report-builder/{id}/share/{shareId}/deactivate', [ReportBuilderController::class, 'apiShareDeactivate'])->name('reports.api.share.deactivate');
    Route::post('/api/report-builder/template', [ReportBuilderController::class, 'apiTemplateSave'])->name('reports.api.template.save');
    Route::delete('/api/report-builder/template/{templateId}', [ReportBuilderController::class, 'apiTemplateDelete'])->name('reports.api.template.delete');
    Route::post('/api/report-builder/{id}/template/apply', [ReportBuilderController::class, 'apiTemplateApply'])->name('reports.api.template.apply');
    Route::get('/api/report-builder/{id}/widgets', [ReportBuilderController::class, 'apiWidgets'])->name('reports.api.widgets');
    Route::post('/api/report-builder/{id}/widget', [ReportBuilderController::class, 'apiWidgetSave'])->name('reports.api.widget.save');
    Route::delete('/api/report-builder/{id}/widget/{widgetId}', [ReportBuilderController::class, 'apiWidgetDelete'])->name('reports.api.widget.delete');
    Route::post('/api/report-builder/{id}/comment', [ReportBuilderController::class, 'apiComment'])->name('reports.api.comment');
    Route::post('/api/report-builder/{id}/attachment', [ReportBuilderController::class, 'apiAttachmentUpload'])->name('reports.api.attachment.upload');
    Route::get('/api/report-builder/{id}/attachments', [ReportBuilderController::class, 'apiAttachments'])->name('reports.api.attachments');
    Route::delete('/api/report-builder/{id}/attachment/{attachmentId}', [ReportBuilderController::class, 'apiAttachmentDelete'])->name('reports.api.attachment.delete');
    Route::post('/api/report-builder/{id}/link', [ReportBuilderController::class, 'apiLinkSave'])->name('reports.api.link.save');
    Route::delete('/api/report-builder/{id}/link/{linkId}', [ReportBuilderController::class, 'apiLinkDelete'])->name('reports.api.link.delete');
    Route::post('/api/report-builder/delete/{id}', [ReportBuilderController::class, 'apiDelete'])->name('reports.api.delete')->where('id', '[0-9]+');
});

// ── Shared report (no auth) ─────────────────────────────────────────
Route::get('/admin/reports/builder/shared/{token}', [ReportBuilderController::class, 'sharedView'])
    ->name('reports.builder.shared')
    ->where('token', '[a-zA-Z0-9]+');
