<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Feedback\Contracts\FeedbackServiceInterface;
use OpenRiC\Feedback\Services\FeedbackService;

class FeedbackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeedbackServiceInterface::class, FeedbackService::class);
    }

    public function boot(): void
    {
        // Public feedback submission (no auth required)
        Route::middleware('web')
            ->group(function (): void {
                Route::post('/feedback', [\OpenRiC\Feedback\Http\Controllers\FeedbackController::class, 'submit'])
                    ->name('feedback.submit');
            });

        // Admin feedback management
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/feedback')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-feedback');
    }
}
