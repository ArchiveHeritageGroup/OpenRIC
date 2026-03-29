<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Feedback\Contracts\FeedbackServiceInterface;
use OpenRiC\Feedback\Services\FeedbackService;

/**
 * Feedback service provider.
 *
 * Adapted from Heratio AhgFeedback\Providers\AhgFeedbackServiceProvider which
 * registered web middleware routes and loaded views from the package directory.
 * OpenRiC adds service binding and separates public/admin route groups.
 */
class FeedbackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeedbackServiceInterface::class, FeedbackService::class);
    }

    public function boot(): void
    {
        // Public feedback routes (no auth required) — mirrors Heratio's public group
        Route::middleware('web')
            ->group(function (): void {
                Route::match(['get', 'post'], '/feedback/general', [
                    \OpenRiC\Feedback\Http\Controllers\FeedbackController::class, 'general',
                ])->name('feedback.general');

                Route::get('/feedback/submit/{slug?}', [
                    \OpenRiC\Feedback\Http\Controllers\FeedbackController::class, 'general',
                ])->name('feedback.submit');

                Route::get('/feedback/submit-success', [
                    \OpenRiC\Feedback\Http\Controllers\FeedbackController::class, 'submitSuccess',
                ])->name('feedback.submit-success');
            });

        // Admin feedback management — mirrors Heratio's admin middleware group
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/feedback')
            ->group(__DIR__ . '/../../routes/web.php');

        // Load Blade views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-feedback');
    }
}
