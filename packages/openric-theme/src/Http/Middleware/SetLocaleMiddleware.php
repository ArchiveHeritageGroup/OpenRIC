<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restore the user's chosen locale from session on every request.
 *
 * Adapted from Heratio sf_culture session pattern — the language.switch route
 * stores the locale in session; this middleware applies it on subsequent requests.
 */
class SetLocaleMiddleware
{
    /** @var string[] RTL language codes */
    private const RTL_LOCALES = ['ar', 'fa', 'he', 'ur'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = Session::get('locale');

        if ($locale !== null && $this->isAvailable($locale)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Check that a lang directory exists for the given locale.
     */
    private function isAvailable(string $locale): bool
    {
        return is_dir(lang_path($locale));
    }

    /**
     * Determine if the current locale is RTL.
     */
    public static function isRtl(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), self::RTL_LOCALES, true);
    }
}
