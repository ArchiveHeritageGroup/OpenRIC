<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Audit\Contracts\AuditServiceInterface;

class AuditController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function browse(Request $request): View
    {
        $filters = $request->only(['action', 'entity_type', 'user_id', 'date_from', 'date_to']);
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);

        $result = $this->auditService->browse($filters, $limit, $offset);

        return view('openric-audit::browse', [
            'items' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function show(int $id): View
    {
        $entry = $this->auditService->find($id);

        if ($entry === null) {
            abort(404);
        }

        return view('openric-audit::show', ['entry' => $entry]);
    }
}
