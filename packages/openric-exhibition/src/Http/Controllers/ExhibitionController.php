<?php

declare(strict_types=1);

namespace OpenRiC\Exhibition\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Exhibition\Contracts\ExhibitionServiceInterface;

/**
 * Exhibition controller — adapted from Heratio ahg-exhibition ExhibitionController (201 lines).
 *
 * Public browse + admin CRUD for exhibitions, objects, storylines, sections, events, checklists.
 */
class ExhibitionController extends Controller
{
    public function __construct(
        private ExhibitionServiceInterface $service,
    ) {}

    // ─── Public ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $filters = array_filter([
            'status'          => $request->get('status'),
            'exhibition_type' => $request->get('type'),
            'year'            => $request->get('year'),
            'search'          => $request->get('search'),
        ]);

        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $result = $this->service->search($filters, $limit, ($page - 1) * $limit);

        $types = $this->service->getTypes();
        $statuses = $this->service->getStatuses();
        $stats = $this->service->getStatistics();

        return view('openric-exhibition::index', [
            'exhibitions' => $result['results'],
            'total'       => $result['total'],
            'page'        => $page,
            'pages'       => (int) ceil($result['total'] / $limit),
            'filters'     => $filters,
            'types'       => $types,
            'statuses'    => $statuses,
            'stats'       => $stats,
        ]);
    }

    public function show(Request $request, string $idOrSlug)
    {
        if (is_numeric($idOrSlug)) {
            $exhibition = $this->service->get((int) $idOrSlug, true);
        } else {
            $ex = $this->service->getBySlug($idOrSlug);
            $exhibition = $ex ? $this->service->get($ex->id, true) : null;
        }

        abort_unless($exhibition, 404, 'Exhibition not found');

        return view('openric-exhibition::show', compact('exhibition'));
    }

    // ─── Dashboard ─────────────────────────────────────────────────────

    public function dashboard()
    {
        $stats = $this->service->getStatistics();
        $result = $this->service->search(['status' => 'active'], 10, 0);

        return view('openric-exhibition::dashboard', [
            'stats'             => $stats,
            'activeExhibitions' => $result['results'],
        ]);
    }

    // ─── Admin CRUD ────────────────────────────────────────────────────

    public function create()
    {
        $types = $this->service->getTypes();
        $statuses = $this->service->getStatuses();

        return view('openric-exhibition::add', compact('types', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'subtitle'        => 'nullable|string|max:255',
            'exhibition_type' => 'required|string|max:50',
            'project_code'    => 'nullable|string|max:100',
            'description'     => 'nullable|string',
            'theme'           => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'venue'           => 'nullable|string|max:255',
            'status'          => 'required|string|max:50',
            'curator'         => 'nullable|string|max:255',
            'designer'        => 'nullable|string|max:255',
            'budget'          => 'nullable|numeric|min:0',
            'budget_currency' => 'nullable|string|max:10',
        ]);

        $validated['created_by'] = Auth::id();

        $id = $this->service->create($validated);

        return redirect()->route('exhibition.show', $id)->with('notice', 'Exhibition created.');
    }

    public function edit(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404, 'Exhibition not found');

        $types = $this->service->getTypes();
        $statuses = $this->service->getStatuses();

        return view('openric-exhibition::edit', compact('exhibition', 'types', 'statuses'));
    }

    public function update(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404, 'Exhibition not found');

        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'subtitle'        => 'nullable|string|max:255',
            'exhibition_type' => 'required|string|max:50',
            'project_code'    => 'nullable|string|max:100',
            'description'     => 'nullable|string',
            'theme'           => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'venue'           => 'nullable|string|max:255',
            'status'          => 'required|string|max:50',
            'curator'         => 'nullable|string|max:255',
            'designer'        => 'nullable|string|max:255',
            'budget'          => 'nullable|numeric|min:0',
            'budget_currency' => 'nullable|string|max:10',
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('exhibition.show', $id)->with('notice', 'Exhibition updated.');
    }

    public function destroy(int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404, 'Exhibition not found');

        $this->service->delete($id);

        return redirect()->route('exhibition.index')->with('notice', 'Exhibition deleted.');
    }

    // ─── Objects ────────────────────────────────────────────────────────

    public function objects(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404);

        return view('openric-exhibition::objects', compact('exhibition'));
    }

    public function objectList(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404);

        return view('openric-exhibition::object-list', compact('exhibition'));
    }

    public function objectListCsv(int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $csv = $this->service->exportObjectListCsv($id);
        $filename = 'exhibition_objects_' . $id . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function addObject(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $validated = $request->validate([
            'entity_iri'    => 'required|string|max:1000',
            'entity_type'   => 'nullable|string|max:50',
            'title'         => 'required|string|max:500',
            'identifier'    => 'nullable|string|max:255',
            'section'       => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'thumbnail_url' => 'nullable|string|max:1000',
        ]);

        $this->service->addObject($id, $validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Object added.']);
        }

        return redirect()->route('exhibition.objects', $id)->with('notice', 'Object added.');
    }

    public function removeObject(Request $request, int $exhibitionId, int $objectId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $this->service->removeObject($exhibitionId, $objectId);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Object removed.']);
        }

        return redirect()->route('exhibition.objects', $exhibitionId)->with('notice', 'Object removed.');
    }

    public function reorderObjects(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $validated = $request->validate([
            'object_ids'   => 'required|array',
            'object_ids.*' => 'integer',
        ]);

        $this->service->reorderObjects($id, $validated['object_ids']);

        return response()->json(['message' => 'Objects reordered.']);
    }

    // ─── Storylines ────────────────────────────────────────────────────

    public function storylines(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404);

        return view('openric-exhibition::storylines', compact('exhibition'));
    }

    public function storyline(int $exhibitionId, int $storylineId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $storyline = $this->service->getStoryline($storylineId);
        abort_unless($storyline, 404);

        return view('openric-exhibition::storyline', compact('exhibition', 'storyline'));
    }

    public function storeStoryline(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->service->createStoryline($id, $validated);

        return redirect()->route('exhibition.storylines', $id)->with('notice', 'Storyline created.');
    }

    public function destroyStoryline(int $exhibitionId, int $storylineId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $this->service->deleteStoryline($storylineId);

        return redirect()->route('exhibition.storylines', $exhibitionId)->with('notice', 'Storyline deleted.');
    }

    // ─── Sections ──────────────────────────────────────────────────────

    public function sections(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404);

        return view('openric-exhibition::sections', compact('exhibition'));
    }

    public function storeSection(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:255',
        ]);

        $this->service->createSection($id, $validated);

        return redirect()->route('exhibition.sections', $id)->with('notice', 'Section created.');
    }

    public function destroySection(int $exhibitionId, int $sectionId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $this->service->deleteSection($sectionId);

        return redirect()->route('exhibition.sections', $exhibitionId)->with('notice', 'Section deleted.');
    }

    // ─── Events ────────────────────────────────────────────────────────

    public function events(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404);

        return view('openric-exhibition::events', compact('exhibition'));
    }

    public function storeEvent(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date'  => 'nullable|date',
            'event_time'  => 'nullable|string|max:10',
            'location'    => 'nullable|string|max:255',
            'event_type'  => 'nullable|string|max:50',
        ]);

        $this->service->createEvent($id, $validated);

        return redirect()->route('exhibition.events', $id)->with('notice', 'Event created.');
    }

    public function destroyEvent(int $exhibitionId, int $eventId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $this->service->deleteEvent($eventId);

        return redirect()->route('exhibition.events', $exhibitionId)->with('notice', 'Event deleted.');
    }

    // ─── Checklists ────────────────────────────────────────────────────

    public function checklists(int $id)
    {
        $exhibition = $this->service->get($id, true);
        abort_unless($exhibition, 404);

        return view('openric-exhibition::checklists', compact('exhibition'));
    }

    public function storeChecklist(Request $request, int $id)
    {
        $exhibition = $this->service->get($id);
        abort_unless($exhibition, 404);

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'assigned_to'  => 'nullable|integer',
            'due_date'     => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $this->service->createChecklist($id, $validated);

        return redirect()->route('exhibition.checklists', $id)->with('notice', 'Checklist item created.');
    }

    public function toggleChecklist(Request $request, int $exhibitionId, int $checklistId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $checklist = DB::table('exhibition_checklists')->where('id', $checklistId)->first();
        abort_unless($checklist, 404);

        $this->service->updateChecklist($checklistId, [
            'is_completed' => !$checklist->is_completed,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Checklist toggled.']);
        }

        return redirect()->route('exhibition.checklists', $exhibitionId)->with('notice', 'Checklist updated.');
    }

    public function destroyChecklist(int $exhibitionId, int $checklistId)
    {
        $exhibition = $this->service->get($exhibitionId);
        abort_unless($exhibition, 404);

        $this->service->deleteChecklist($checklistId);

        return redirect()->route('exhibition.checklists', $exhibitionId)->with('notice', 'Checklist item deleted.');
    }
}
