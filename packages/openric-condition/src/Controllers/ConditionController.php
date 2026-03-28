<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Condition\Contracts\ConditionServiceInterface;

class ConditionController extends Controller
{
    public function __construct(
        private readonly ConditionServiceInterface $conditionService,
    ) {}

    public function index(Request $request): View
    {
        $result = $this->conditionService->browse(
            $request->only(['condition_code']),
            (int) $request->get('limit', 25),
            (int) $request->get('offset', 0)
        );

        return view('condition::index', [
            'items' => $result['items'],
            'total' => $result['total'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('condition::create', ['object_iri' => $request->get('object_iri', '')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'object_iri' => 'required|string',
            'condition_code' => 'required|string|max:50',
            'condition_label' => 'required|string|max:255',
            'conservation_priority' => 'nullable|integer|min:0|max:5',
            'completeness_pct' => 'nullable|integer|min:0|max:100',
            'storage_requirements' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'next_assessment_date' => 'nullable|date',
        ]);

        $this->conditionService->assess($data['object_iri'], $data, Auth::id());

        return redirect()->route('condition.index')->with('success', 'Condition assessment recorded.');
    }
}
