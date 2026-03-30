<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Auth\Contracts\AclServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class CheckAcl
{
    public function __construct(
        private readonly AclServiceInterface $aclService,
    ) {}

    public function handle(Request $request, Closure $next, string $action = 'read'): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        if (! $userId || ! $this->aclService->check($userId, $action)) {
            abort(403, 'Insufficient permissions');
        }

        return $next($request);
    }
}
