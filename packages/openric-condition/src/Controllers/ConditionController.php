<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Condition\Contracts\ConditionServiceInterface;

/**
 * Condition controller — adapted from Heratio ConditionController (206 lines).
 *
 * Actions: admin dashboard, browse, view, create, store, photos, annotations,
 * upload, delete photo, export, templates.
 */
class ConditionController extends Controller
{
    public function __construct(
        private readonly ConditionServiceInterface $conditionService,
    ) {}

    /**
     * Admin dashboard with stats and recent checks.
     */
    public function admin(): View
    {
        $stats = $this->conditionService->getAdminStats();
        $recentChecks = $this->conditionService->getRecentChecks();
        $breakdown = $this->conditionService->getConditionBreakdown();
        $upcoming = $this->conditionService->getUpcoming();

        return view('condition::admin', compact('stats', 'recentChecks', 'breakdown', 'upcoming'));
    }

    /**
     * Browse assessments with filters and pagination.
     */
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = (int) $request->input('limit', 25);
        $offset = ($page - 1) * $limit;

        $filters = $request->only(['condition_code', 'object_iri', 'assessed_by', 'date_from', 'date_to', 'priority_min']);
        $result = $this->conditionService->browse($filters, $limit, $offset);
        $totalPages = max(1, (int) ceil($result['total'] / $limit));

        return view('condition::index', [
            'items' => $result['items'], 'total' => $result['total'],
            'page' => $page, 'limit' => $limit, 'totalPages' => $totalPages, 'filters' => $filters,
        ]);
    }

    /**
     * View single assessment with photos and annotations.
     */
    public function show(int $id): View
    {
        $check = $this->conditionService->find($id);
        if (!$check) { abort(404, 'Assessment not found'); }

        $history = $this->conditionService->getHistory($check->object_iri);
        $templates = $this->conditionService->getTemplates();

        return view('condition::show', compact('check', 'history', 'templates'));
    }

    /**
     * Create form.
     */
    public function create(Request $request): View
    {
        $templates = $this->conditionService->getTemplates();
        return view('condition::create', ['object_iri' => $request->input('object_iri', ''), 'templates' => $templates]);
    }

    /**
     * Store new assessment.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'object_iri' => 'required|string|max:2048',
            'condition_code' => 'required|string|max:50',
            'condition_label' => 'required|string|max:255',
            'conservation_priority' => 'nullable|integer|min:0|max:5',
            'completeness_pct' => 'nullable|integer|min:0|max:100',
            'hazards' => 'nullable|array',
            'storage_requirements' => 'nullable|string|max:65535',
            'recommendations' => 'nullable|string|max:65535',
            'notes' => 'nullable|string|max:65535',
            'next_assessment_date' => 'nullable|date',
        ]);

        $id = $this->conditionService->assess($data['object_iri'], $data, Auth::id());

        return redirect()->route('conditions.show', $id)->with('success', 'Condition assessment recorded.');
    }

    /**
     * Upload photo to assessment.
     */
    public function uploadPhoto(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|max:10240',
            'photo_type' => 'required|string|in:before,after,damage,overview',
            'caption' => 'nullable|string|max:500',
        ]);

        $this->conditionService->uploadPhoto($id, $request->file('photo'), $request->input('photo_type'), $request->input('caption', ''), Auth::id());

        return redirect()->route('conditions.show', $id)->with('success', 'Photo uploaded.');
    }

    /**
     * Delete photo.
     */
    public function deletePhoto(int $id): RedirectResponse
    {
        $photo = $this->conditionService->getPhoto($id);
        if (!$photo) { abort(404); }

        $this->conditionService->deletePhoto($id, Auth::id());

        return redirect()->back()->with('success', 'Photo deleted.');
    }

    /**
     * Get annotations for photo (JSON).
     */
    public function getAnnotations(int $photoId): JsonResponse
    {
        return response()->json($this->conditionService->getAnnotations($photoId));
    }

    /**
     * Save annotations for photo (JSON).
     */
    public function saveAnnotations(Request $request, int $photoId): JsonResponse
    {
        $request->validate(['annotations' => 'required|array']);
        $this->conditionService->saveAnnotations($photoId, $request->input('annotations'), Auth::id());
        return response()->json(['success' => true]);
    }

    /**
     * Export condition report.
     */
    public function exportReport(int $id): JsonResponse
    {
        $report = $this->conditionService->generateReport($id);
        if (!$report) { abort(404); }
        return response()->json($report);
    }

    /**
     * List templates.
     */
    public function templates(): View
    {
        $templates = $this->conditionService->getTemplates();
        return view('condition::templates', compact('templates'));
    }
}
