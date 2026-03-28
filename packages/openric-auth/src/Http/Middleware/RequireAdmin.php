<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Auth\Contracts\AclServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{
    public function __construct(
        private readonly AclServiceInterface $aclService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            abort(403, 'Insufficient permissions');
        }

        if (! $this->aclService->canAdmin(Auth::id())) {
            abort(403, 'Insufficient permissions');
        }

        return $next($request);
    }
}
