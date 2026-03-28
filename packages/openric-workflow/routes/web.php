<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Workflow\Controllers\WorkflowController;

/*
|--------------------------------------------------------------------------
| Workflow Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /workflow and protected by auth middleware.
| The prefix and middleware are applied in WorkflowServiceProvider::boot().
|
*/

// Dashboard
Route::get('/', [WorkflowController::class, 'dashboard'])->name('workflow.dashboard');

// My tasks
Route::get('/my-tasks', [WorkflowController::class, 'myTasks'])->name('workflow.my-tasks');

// Pool tasks
Route::get('/pool', [WorkflowController::class, 'poolTasks'])->name('workflow.pool');

// Overdue tasks
Route::get('/overdue', [WorkflowController::class, 'overdue'])->name('workflow.overdue');

// Publish readiness
Route::get('/publish-readiness', [WorkflowController::class, 'publishReadiness'])->name('workflow.publish-readiness');

// Single task view
Route::get('/task/{id}', [WorkflowController::class, 'showTask'])->name('workflow.task')->where('id', '[0-9]+');

// Task actions (POST)
Route::post('/task/{id}/claim', [WorkflowController::class, 'claimTask'])->name('workflow.task.claim')->where('id', '[0-9]+');
Route::post('/task/{id}/release', [WorkflowController::class, 'releaseTask'])->name('workflow.task.release')->where('id', '[0-9]+');
Route::post('/task/{id}/approve', [WorkflowController::class, 'approveTask'])->name('workflow.task.approve')->where('id', '[0-9]+');
Route::post('/task/{id}/reject', [WorkflowController::class, 'rejectTask'])->name('workflow.task.reject')->where('id', '[0-9]+');

// Admin: workflow management
Route::get('/admin', [WorkflowController::class, 'workflows'])->name('workflow.admin');
Route::get('/admin/create', [WorkflowController::class, 'createWorkflow'])->name('workflow.admin.create');
Route::post('/admin/create', [WorkflowController::class, 'storeWorkflow'])->name('workflow.admin.store');
Route::get('/admin/{id}/edit', [WorkflowController::class, 'editWorkflow'])->name('workflow.admin.edit')->where('id', '[0-9]+');
Route::post('/admin/{id}/edit', [WorkflowController::class, 'updateWorkflow'])->name('workflow.admin.update')->where('id', '[0-9]+');
Route::post('/admin/{id}/delete', [WorkflowController::class, 'deleteWorkflow'])->name('workflow.admin.delete')->where('id', '[0-9]+');

// Admin: step management
Route::post('/admin/{workflowId}/step', [WorkflowController::class, 'addStep'])->name('workflow.admin.step.add')->where('workflowId', '[0-9]+');
Route::post('/admin/step/{id}/delete', [WorkflowController::class, 'deleteStep'])->name('workflow.admin.step.delete')->where('id', '[0-9]+');
