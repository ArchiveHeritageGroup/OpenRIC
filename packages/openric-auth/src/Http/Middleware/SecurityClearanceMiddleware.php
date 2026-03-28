<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Auth\Contracts\SecurityClearanceServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class SecurityClearanceMiddleware
{
    public function __construct(
        private readonly SecurityClearanceServiceInterface $clearanceService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $objectIri = $request->route('iri');
        if ($objectIri !== null && ! $this->clearanceService->canAccessObject(Auth::id(), $objectIri)) {
            abort(403, 'Insufficient security clearance');
        }

        return $next($request);
    }
}
