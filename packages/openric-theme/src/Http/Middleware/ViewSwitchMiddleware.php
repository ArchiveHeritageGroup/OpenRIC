<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewSwitchMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $viewParam = $request->query('view');

        if ($viewParam !== null && in_array($viewParam, ['ric', 'traditional', 'graph'], true)) {
            session(['openric_view_mode' => $viewParam]);
        }

        return $next($request);
    }
}
