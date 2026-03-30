<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * RightsHolderController — CRUD for rights holder entities.
 *
 * Adapted from Heratio RightsHolderController (143 lines) + RightsHolderService (391 lines).
 * Heratio uses slug-based routing with AtoM's object/actor/rights_holder/actor_i18n/slug
 * tables plus contact_information + contact_information_i18n for addresses.
 *
 * OpenRiC stores rights holders in a single PostgreSQL `rights_holders` table with
 * entity_iri references and a `rights_holder_contacts` table for contact information.
 */
class RightsHolderController extends Controller
{
    /**
     * Browse rights holders with search, sort, pagination.
     *
     * Adapted from Heratio RightsHolderController::browse() which uses
     * RightsHolderBrowseService + SimplePager.
     */
    public function browse(Request $request): View
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, min(100, (int) $request->get('limit', 25)));
        $offset = ($page - 1) * $limit;
        $sort = $request->get('sort', 'alphabetic');
        $sortDir = strtolower($request->get('sortDir', '')) === 'desc' ? 'desc' : 'asc';
        $subquery = trim($request->get('subquery', ''));

        $query = DB::table('rights_holders')->whereNull('deleted_at');

        if ($subquery !== '') {
            $query->where(function ($q) use ($subquery) {
                $q->where('name', 'ILIKE', "%{$subquery}%")
                  ->orWhere('description_identifier', 'ILIKE', "%{$subquery}%");
            });
        }

        $total = $query->count();

        match ($sort) {
            'alphabetic' => $query->orderBy('name', $sortDir),
            'identifier' => $query->orderBy('description_identifier', $sortDir),
            'lastUpdated' => $query->orderBy('updated_at', $sortDir),
            default => $query->orderBy('name', $sortDir),
        };

        $hits = $query->select([
                'id', 'uuid', 'name', 'description_identifier',
                'entity_iri', 'updated_at',
            ])
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        return view('rights::rightsHolder.browse', [
            'hits'    => $hits,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'filters' => $request->only(['sort', 'sortDir', 'subquery']),
            'sortOptions' => [
                'alphabetic'  => 'Name',
                'lastUpdated' => 'Date modified',
                'identifier'  => 'Identifier',
            ],
        ]);
    }

    /**
     * Show a single rights holder with contacts and related rights.
     *
     * Adapted from Heratio RightsHolderController::show() which resolves slug,
     * fetches contacts, PREMIS rights, and extended rights for the holder.
     */
    public function show(int $id): View
    {
        $rightsHolder = DB::table('rights_holders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rightsHolder) {
            abort(404, 'Rights holder not found.');
        }

        $contacts = DB::table('rights_holder_contacts')
            ->where('rights_holder_id', $id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $relatedRights = DB::table('rights_statements')
            ->where('rights_holder_name', $rightsHolder->name)
            ->orWhere('rights_holder_iri', $rightsHolder->entity_iri)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        return view('rights::rightsHolder.show', [
            'rightsHolder'  => $rightsHolder,
            'contacts'      => $contacts,
            'relatedRights' => $relatedRights,
        ]);
    }

    /**
     * Show create form.
     *
     * Adapted from Heratio RightsHolderController::create() which provides
     * an empty contact object for the form.
     */
    public function create(): View
    {
        return view('rights::rightsHolder.edit', [
            'rightsHolder' => null,
            'contacts'     => collect(),
        ]);
    }

    /**
     * Show edit form.
     *
     * Adapted from Heratio RightsHolderController::edit() which resolves slug,
     * fetches rights holder and contacts.
     */
    public function edit(int $id): View
    {
        $rightsHolder = DB::table('rights_holders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rightsHolder) {
            abort(404, 'Rights holder not found.');
        }

        $contacts = DB::table('rights_holder_contacts')
            ->where('rights_holder_id', $id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return view('rights::rightsHolder.edit', [
            'rightsHolder' => $rightsHolder,
            'contacts'     => $contacts,
        ]);
    }

    /**
     * Store a new rights holder.
     *
     * Adapted from Heratio RightsHolderController::store() which validates
     * authorized_form_of_name then calls RightsHolderService::create()
     * (5-table transaction + contact sync).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                   => 'required|string|max:1024',
            'description_identifier' => 'nullable|string|max:255',
            'corporate_body_ids'     => 'nullable|string|max:1024',
            'history'                => 'nullable|string',
            'places'                 => 'nullable|string',
            'legal_status'           => 'nullable|string',
            'functions'              => 'nullable|string',
            'mandates'               => 'nullable|string',
            'internal_structures'    => 'nullable|string',
            'general_context'        => 'nullable|string',
            'rules'                  => 'nullable|string',
            'sources'                => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        $id = DB::transaction(function () use ($request): int {
            $rhId = DB::table('rights_holders')->insertGetId([
                'uuid'                   => (string) Str::uuid(),
                'name'                   => $request->input('name'),
                'entity_iri'             => $request->input('entity_iri', ''),
                'description_identifier' => $request->input('description_identifier'),
                'corporate_body_ids'     => $request->input('corporate_body_ids'),
                'history'                => $request->input('history'),
                'places'                 => $request->input('places'),
                'legal_status'           => $request->input('legal_status'),
                'functions'              => $request->input('functions'),
                'mandates'               => $request->input('mandates'),
                'internal_structures'    => $request->input('internal_structures'),
                'general_context'        => $request->input('general_context'),
                'rules'                  => $request->input('rules'),
                'sources'                => $request->input('sources'),
                'notes'                  => $request->input('notes'),
                'created_by'             => auth()->id(),
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            $this->saveContacts($rhId, $request->input('contacts', []));

            return $rhId;
        });

        return redirect()
            ->route('rights.holders.show', $id)
            ->with('success', 'Rights holder created successfully.');
    }

    /**
     * Update an existing rights holder.
     *
     * Adapted from Heratio RightsHolderController::update() which validates
     * then calls RightsHolderService::update() (multi-table update + contact sync).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $rightsHolder = DB::table('rights_holders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rightsHolder) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:1024',
        ]);

        DB::transaction(function () use ($id, $request): void {
            $fields = [
                'name', 'entity_iri', 'description_identifier', 'corporate_body_ids',
                'history', 'places', 'legal_status', 'functions', 'mandates',
                'internal_structures', 'general_context', 'rules', 'sources', 'notes',
            ];

            $update = [];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $update[$field] = $request->input($field);
                }
            }
            $update['updated_at'] = now();

            DB::table('rights_holders')->where('id', $id)->update($update);

            if ($request->has('contacts')) {
                $this->syncContacts($id, $request->input('contacts', []));
            }
        });

        return redirect()
            ->route('rights.holders.show', $id)
            ->with('success', 'Rights holder updated successfully.');
    }

    /**
     * Show delete confirmation.
     *
     * Adapted from Heratio RightsHolderController::confirmDelete().
     */
    public function confirmDelete(int $id): View
    {
        $rightsHolder = DB::table('rights_holders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rightsHolder) {
            abort(404);
        }

        return view('rights::rightsHolder.delete', ['rightsHolder' => $rightsHolder]);
    }

    /**
     * Soft-delete a rights holder.
     *
     * Adapted from Heratio RightsHolderController::destroy() which hard-deletes
     * from 14+ tables. OpenRiC uses soft delete.
     */
    public function destroy(int $id): RedirectResponse
    {
        $rightsHolder = DB::table('rights_holders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rightsHolder) {
            abort(404);
        }

        DB::table('rights_holders')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return redirect()
            ->route('rights.holders.browse')
            ->with('success', 'Rights holder deleted successfully.');
    }

    /**
     * Save new contacts for a rights holder.
     *
     * Adapted from Heratio RightsHolderService::saveContacts() which inserts
     * into contact_information + contact_information_i18n tables.
     */
    protected function saveContacts(int $rightsHolderId, array $contacts): void
    {
        foreach ($contacts as $c) {
            if ($this->isContactEmpty($c)) {
                continue;
            }

            DB::table('rights_holder_contacts')->insert([
                'rights_holder_id' => $rightsHolderId,
                'is_primary'       => !empty($c['is_primary']) ? true : false,
                'contact_person'   => $c['contact_person'] ?? null,
                'telephone'        => $c['telephone'] ?? null,
                'fax'              => $c['fax'] ?? null,
                'email'            => $c['email'] ?? null,
                'website'          => $c['website'] ?? null,
                'street_address'   => $c['street_address'] ?? null,
                'city'             => $c['city'] ?? null,
                'region'           => $c['region'] ?? null,
                'country_code'     => $c['country_code'] ?? null,
                'postal_code'      => $c['postal_code'] ?? null,
                'latitude'         => $c['latitude'] ?? null,
                'longitude'        => $c['longitude'] ?? null,
                'contact_type'     => $c['contact_type'] ?? null,
                'note'             => $c['note'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    /**
     * Sync contacts for update — handles create, update, delete.
     *
     * Adapted from Heratio RightsHolderService::syncContacts() which handles
     * contact_information + contact_information_i18n upserts and deletions.
     */
    protected function syncContacts(int $rightsHolderId, array $contacts): void
    {
        foreach ($contacts as $c) {
            if (!empty($c['delete']) && !empty($c['id'])) {
                DB::table('rights_holder_contacts')->where('id', $c['id'])->delete();
                continue;
            }
            if ($this->isContactEmpty($c)) {
                continue;
            }

            $contactData = [
                'is_primary'     => !empty($c['is_primary']) ? true : false,
                'contact_person' => $c['contact_person'] ?? null,
                'telephone'      => $c['telephone'] ?? null,
                'fax'            => $c['fax'] ?? null,
                'email'          => $c['email'] ?? null,
                'website'        => $c['website'] ?? null,
                'street_address' => $c['street_address'] ?? null,
                'city'           => $c['city'] ?? null,
                'region'         => $c['region'] ?? null,
                'country_code'   => $c['country_code'] ?? null,
                'postal_code'    => $c['postal_code'] ?? null,
                'latitude'       => $c['latitude'] ?? null,
                'longitude'      => $c['longitude'] ?? null,
                'contact_type'   => $c['contact_type'] ?? null,
                'note'           => $c['note'] ?? null,
                'updated_at'     => now(),
            ];

            if (!empty($c['id'])) {
                DB::table('rights_holder_contacts')
                    ->where('id', $c['id'])
                    ->where('rights_holder_id', $rightsHolderId)
                    ->update($contactData);
            } else {
                $contactData['rights_holder_id'] = $rightsHolderId;
                $contactData['created_at'] = now();
                DB::table('rights_holder_contacts')->insert($contactData);
            }
        }
    }

    /**
     * Check if a contact array is effectively empty.
     *
     * Adapted from Heratio RightsHolderService::isContactEmpty().
     */
    protected function isContactEmpty(array $d): bool
    {
        foreach (['contact_person', 'street_address', 'website', 'email', 'telephone', 'fax', 'city', 'region', 'postal_code', 'country_code'] as $f) {
            if (!empty($d[$f])) {
                return false;
            }
        }
        return true;
    }
}
