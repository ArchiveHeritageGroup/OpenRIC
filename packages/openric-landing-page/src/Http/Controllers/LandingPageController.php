<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\LandingPage\Contracts\LandingPageServiceInterface;

/**
 * Landing page controller -- adapted from Heratio AhgLandingPage\Controllers\LandingPageController (239 lines).
 */
class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $service,
    ) {}

    /**
     * Public landing page.
     */
    public function index(): JsonResponse
    {
        $content = $this->service->getPageContent();
        $widgets = $this->service->getWidgets();
        $stats   = $this->service->getStats();

        return response()->json([
            'content' => $content,
            'widgets' => $widgets,
            'stats'   => $stats,
        ]);
    }

    /**
     * Admin: get current landing page settings.
     */
    public function admin(): JsonResponse
    {
        $content = $this->service->getPageContent();
        $widgets = $this->service->getWidgets();

        return response()->json([
            'content' => $content,
            'widgets' => $widgets,
        ]);
    }

    /**
     * Admin: update landing page content and widget config.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'title'            => 'nullable|string|max:255',
            'subtitle'         => 'nullable|string|max:500',
            'hero_image'       => 'nullable|string|max:2048',
            'about_text'       => 'nullable|string|max:10000',
            'footer_text'      => 'nullable|string|max:5000',
            'meta_description' => 'nullable|string|max:500',
            'widgets'          => 'nullable|array',
        ]);

        $this->service->updatePageContent($request->only([
            'title', 'subtitle', 'hero_image', 'about_text',
            'footer_text', 'meta_description',
        ]));

        if ($request->has('widgets') && is_array($request->input('widgets'))) {
            $keys = array_column($request->input('widgets'), 'key');
            if (!empty($keys)) {
                $this->service->reorderWidgets($keys);
            }
        }

        return response()->json(['success' => true, 'message' => 'Landing page updated.']);
    }
}
