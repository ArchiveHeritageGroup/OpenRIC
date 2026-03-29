<?php

declare(strict_types=1);

/**
 * Routes are registered in LandingPageServiceProvider::boot() directly
 * to keep middleware and prefix configuration co-located with route definitions.
 *
 * This file exists for autoloader compatibility but all route registration
 * happens in the service provider.
 */
