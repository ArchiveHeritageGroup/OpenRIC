<?php

declare(strict_types=1);

namespace OpenRiC\Research\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Research\Contracts\ResearchServiceInterface;

/**
 * ResearchService -- Core Research Portal Service.
 *
 * Handles researcher management, reading-room bookings, collections, annotations,
 * citations, saved searches, researcher types, API keys, notifications, dashboard,
 * walk-in visitors, and HTML sanitisation.
 *
 * Adapted from Heratio AhgResearch\Services\ResearchService (608 LOC).
 * PostgreSQL ILIKE used for all text searches.
 */
class ResearchService implements ResearchServiceInterface
{
    // =========================================================================
    // RESEARCHER MANAGEMENT
    // =========================================================================

    public function getResearcherByUserId(int $userId): ?object
    {
        return DB::table('research_researcher')->where('user_id', $userId)->first();
    }

    public function getResearcher(int $id): ?object
    {
        return DB::table('research_researcher')->where('id', $id)->first();
    }

    public function getResearcherByEmail(string $email): ?object
    {
        return DB::table('research_researcher')->where('email', $email)->first();
    }

    public function registerResearcher(array $data): int
    {
        $researcherId = DB::table('research_researcher')->insertGetId([
            'user_id'            => $data['user_id'],
            'title'              => $data['title'] ?? null,
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'email'              => $data['email'],
            'phone'              => $data['phone'] ?? null,
            'affiliation_type'   => $data['affiliation_type'] ?? 'independent',
            'institution'        => $data['institution'] ?? null,
            'department'         => $data['department'] ?? null,
            'position'           => $data['position'] ?? null,
            'student_id'         => $data['student_id'] ?? null,
            'research_interests' => $data['research_interests'] ?? null,
            'current_project'    => $data['current_project'] ?? null,
            'orcid_id'           => $data['orcid_id'] ?? null,
            'id_type'            => ($data['id_type'] ?? null) ?: null,
            'id_number'          => $data['id_number'] ?? null,
            'status'             => 'pending',
            'created_at'         => now(),
        ]);

        // Access request for researcher approval
        DB::table('access_request')->insert([
            'request_type'               => 'researcher',
            'scope_type'                 => 'single',
            'user_id'                    => $data['user_id'],
            'requested_classification_id' => 2,
            'current_classification_id'   => 1,
            'reason'                     => 'New researcher registration: ' . $data['first_name'] . ' ' . $data['last_name'],
            'justification'              => $data['research_interests'] ?? $data['current_project'] ?? null,
            'urgency'                    => 'normal',
            'status'                     => 'pending',
            'created_at'                 => now(),
        ]);

        $this->logAudit('create', 'Researcher', $researcherId, [], $data, $data['first_name'] . ' ' . $data['last_name']);

        return $researcherId;
    }

    public function updateResearcher(int $id, array $data): bool
    {
        $oldValues = (array) (DB::table('research_researcher')->where('id', $id)->first() ?? new \stdClass());
        $data['updated_at'] = now();
        $result = DB::table('research_researcher')->where('id', $id)->update($data) > 0;

        if ($result) {
            $newValues = (array) (DB::table('research_researcher')->where('id', $id)->first() ?? new \stdClass());
            $this->logAudit('update', 'Researcher', $id, $oldValues, $newValues, ($newValues['first_name'] ?? '') . ' ' . ($newValues['last_name'] ?? ''));
        }

        return $result;
    }

    public function approveResearcher(int $id, int $approvedBy, ?string $expiresAt = null): bool
    {
        $oldValues = (array) (DB::table('research_researcher')->where('id', $id)->first() ?? new \stdClass());
        $result = DB::table('research_researcher')->where('id', $id)->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'expires_at'  => $expiresAt ?? date('Y-m-d', strtotime('+1 year')),
        ]) > 0;

        if ($result) {
            $newValues = (array) (DB::table('research_researcher')->where('id', $id)->first() ?? new \stdClass());
            $this->logAudit('approve', 'Researcher', $id, $oldValues, $newValues, ($newValues['first_name'] ?? '') . ' ' . ($newValues['last_name'] ?? ''));
        }

        return $result;
    }

    public function rejectResearcher(int $id, int $rejectedBy, string $reason = ''): bool
    {
        $researcher = DB::table('research_researcher')->where('id', $id)->first();
        if (!$researcher) {
            return false;
        }

        // Archive to audit table
        DB::table('research_researcher_audit')->insert([
            'original_id'        => $researcher->id,
            'user_id'            => $researcher->user_id,
            'title'              => $researcher->title,
            'first_name'         => $researcher->first_name,
            'last_name'          => $researcher->last_name,
            'email'              => $researcher->email,
            'phone'              => $researcher->phone,
            'affiliation_type'   => $researcher->affiliation_type,
            'institution'        => $researcher->institution,
            'department'         => $researcher->department,
            'position'           => $researcher->position,
            'research_interests' => $researcher->research_interests,
            'current_project'    => $researcher->current_project,
            'orcid_id'           => $researcher->orcid_id,
            'id_type'            => $researcher->id_type,
            'id_number'          => $researcher->id_number,
            'status'             => 'rejected',
            'rejection_reason'   => $reason,
            'archived_by'        => $rejectedBy,
            'archived_at'        => now(),
            'original_created_at' => $researcher->created_at,
            'original_updated_at' => $researcher->updated_at ?? null,
        ]);

        DB::table('research_researcher')->where('id', $id)->delete();
        DB::table('users')->where('id', $researcher->user_id)->update(['active' => false]);

        return true;
    }

    public function suspendResearcher(int $id): bool
    {
        $researcher = DB::table('research_researcher')->where('id', $id)->first();
        if (!$researcher) {
            return false;
        }

        DB::table('research_researcher')->where('id', $id)->update([
            'status'     => 'suspended',
            'updated_at' => now(),
        ]);
        DB::table('users')->where('id', $researcher->user_id)->update(['active' => false]);

        return true;
    }

    public function getResearchers(array $filters = []): array
    {
        $query = DB::table('research_researcher');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', $search)
                  ->orWhere('last_name', 'ILIKE', $search)
                  ->orWhere('email', 'ILIKE', $search)
                  ->orWhere('institution', 'ILIKE', $search);
            });
        }

        return $query->orderBy('last_name')->get()->toArray();
    }

    public function getResearcherCounts(): array
    {
        return [
            'all'       => (int) DB::table('research_researcher')->count(),
            'pending'   => (int) DB::table('research_researcher')->where('status', 'pending')->count(),
            'approved'  => (int) DB::table('research_researcher')->where('status', 'approved')->count(),
            'suspended' => (int) DB::table('research_researcher')->where('status', 'suspended')->count(),
            'expired'   => (int) DB::table('research_researcher')->where('status', 'expired')->count(),
        ];
    }

    // =========================================================================
    // READING ROOMS & BOOKINGS
    // =========================================================================

    public function getReadingRooms(bool $activeOnly = true): array
    {
        $query = DB::table('research_reading_room');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')->get()->toArray();
    }

    public function getReadingRoom(int $id): ?object
    {
        return DB::table('research_reading_room')->where('id', $id)->first();
    }

    public function createReadingRoom(array $data): int
    {
        $data['created_at'] = now();

        return DB::table('research_reading_room')->insertGetId($data);
    }

    public function updateReadingRoom(int $id, array $data): bool
    {
        return DB::table('research_reading_room')->where('id', $id)->update($data) >= 0;
    }

    public function createBooking(array $data): int
    {
        $bookingId = DB::table('research_booking')->insertGetId([
            'researcher_id'  => $data['researcher_id'],
            'reading_room_id' => $data['reading_room_id'],
            'booking_date'   => $data['booking_date'],
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'],
            'purpose'        => $data['purpose'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'status'         => 'pending',
            'created_at'     => now(),
        ]);

        $this->logAudit('create', 'ResearchBooking', $bookingId, [], $data, 'Booking ' . $data['booking_date']);

        return $bookingId;
    }

    public function addMaterialRequest(int $bookingId, int $objectId, ?string $notes = null): int
    {
        return DB::table('research_material_request')->insertGetId([
            'booking_id' => $bookingId,
            'object_id'  => $objectId,
            'notes'      => $notes,
            'status'     => 'requested',
            'created_at' => now(),
        ]);
    }

    public function getBooking(int $id): ?object
    {
        $booking = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.id', $id)
            ->select('b.*', 'r.first_name', 'r.last_name', 'r.email', 'r.institution',
                'rm.name as room_name', 'rm.location as room_location')
            ->first();

        if ($booking) {
            $booking->materials = DB::table('research_material_request')
                ->where('booking_id', $id)
                ->get()->toArray();
        }

        return $booking;
    }

    public function getResearcherBookings(int $researcherId, ?string $status = null): array
    {
        $query = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $researcherId);

        if ($status) {
            $query->where('b.status', $status);
        }

        return $query->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date', 'desc')->get()->toArray();
    }

    public function confirmBooking(int $id, int $confirmedBy): bool
    {
        $oldValues = (array) (DB::table('research_booking')->where('id', $id)->first() ?? new \stdClass());
        $result = DB::table('research_booking')->where('id', $id)->update([
            'status'       => 'confirmed',
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
        ]) > 0;

        if ($result) {
            $newValues = (array) (DB::table('research_booking')->where('id', $id)->first() ?? new \stdClass());
            $this->logAudit('confirm', 'ResearchBooking', $id, $oldValues, $newValues, null);
        }

        return $result;
    }

    public function cancelBooking(int $id, ?string $reason = null): bool
    {
        $oldValues = (array) (DB::table('research_booking')->where('id', $id)->first() ?? new \stdClass());
        $result = DB::table('research_booking')->where('id', $id)->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
        ]) > 0;

        if ($result) {
            $newValues = (array) (DB::table('research_booking')->where('id', $id)->first() ?? new \stdClass());
            $this->logAudit('cancel', 'ResearchBooking', $id, $oldValues, $newValues, null);
        }

        return $result;
    }

    public function checkIn(int $bookingId): bool
    {
        return DB::table('research_booking')->where('id', $bookingId)
            ->update(['checked_in_at' => now(), 'status' => 'confirmed']) > 0;
    }

    public function checkOut(int $bookingId): bool
    {
        $updated = DB::table('research_booking')->where('id', $bookingId)->update([
            'status'         => 'completed',
            'checked_out_at' => now(),
        ]) > 0;

        if ($updated) {
            DB::table('research_material_request')
                ->where('booking_id', $bookingId)
                ->where('status', '!=', 'returned')
                ->update(['status' => 'returned', 'returned_at' => now()]);
        }

        return $updated;
    }

    public function noShowBooking(int $id): bool
    {
        return DB::table('research_booking')->where('id', $id)->update(['status' => 'no_show']) > 0;
    }

    // =========================================================================
    // SEATS & EQUIPMENT
    // =========================================================================

    public function getSeats(int $roomId): array
    {
        return DB::table('research_reading_room_seat')
            ->where('reading_room_id', $roomId)
            ->orderBy('seat_number')
            ->get()->toArray();
    }

    public function getEquipment(int $roomId): array
    {
        return DB::table('research_equipment')
            ->where('reading_room_id', $roomId)
            ->orderBy('name')
            ->get()->toArray();
    }

    // =========================================================================
    // WALK-IN VISITORS
    // =========================================================================

    public function registerWalkIn(int $roomId, array $data, int $registeredBy): int
    {
        return DB::table('research_walk_in_visitor')->insertGetId([
            'reading_room_id'    => $roomId,
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'id_type'            => $data['id_type'] ?? null,
            'id_number'          => $data['id_number'] ?? null,
            'organization'       => $data['organization'] ?? null,
            'purpose'            => $data['purpose'] ?? null,
            'research_topic'     => $data['research_topic'] ?? null,
            'rules_acknowledged' => ($data['rules_acknowledged'] ?? false) ? true : false,
            'visit_date'         => date('Y-m-d'),
            'checked_in_at'      => now(),
            'checked_in_by'      => $registeredBy,
        ]);
    }

    public function checkOutWalkIn(int $visitorId, int $checkedOutBy): bool
    {
        return DB::table('research_walk_in_visitor')
            ->where('id', $visitorId)
            ->update([
                'checked_out_at' => now(),
                'checked_out_by' => $checkedOutBy,
            ]) > 0;
    }

    public function getCurrentWalkIns(int $roomId): array
    {
        return DB::table('research_walk_in_visitor')
            ->where('reading_room_id', $roomId)
            ->where('visit_date', date('Y-m-d'))
            ->whereNull('checked_out_at')
            ->orderBy('checked_in_at')
            ->get()->toArray();
    }

    // =========================================================================
    // RETRIEVAL QUEUE
    // =========================================================================

    public function getRetrievalQueue(): array
    {
        return DB::table('research_material_request as m')
            ->join('research_booking as b', 'm.booking_id', '=', 'b.id')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->whereIn('m.status', ['requested', 'in_transit'])
            ->select('m.*', 'b.booking_date', 'b.start_time',
                'r.first_name', 'r.last_name')
            ->orderBy('b.booking_date')
            ->get()->toArray();
    }

    // =========================================================================
    // SAVED SEARCHES
    // =========================================================================

    public function saveSearch(int $researcherId, array $data): int
    {
        return DB::table('research_saved_search')->insertGetId([
            'researcher_id'  => $researcherId,
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'search_query'   => $data['search_query'],
            'search_filters' => isset($data['search_filters']) ? json_encode($data['search_filters']) : null,
            'alert_enabled'  => $data['alert_enabled'] ?? false,
            'created_at'     => now(),
        ]);
    }

    public function getSavedSearches(int $researcherId): array
    {
        return DB::table('research_saved_search')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
    }

    public function getSavedSearch(int $id): ?object
    {
        return DB::table('research_saved_search')->where('id', $id)->first();
    }

    public function deleteSavedSearch(int $id, int $researcherId): bool
    {
        return DB::table('research_saved_search')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->delete() > 0;
    }

    public function runSavedSearch(int $id): bool
    {
        return DB::table('research_saved_search')
            ->where('id', $id)
            ->update(['last_run_at' => now()]) >= 0;
    }

    // =========================================================================
    // COLLECTIONS (Evidence Sets)
    // =========================================================================

    public function createCollection(int $researcherId, array $data): int
    {
        return DB::table('research_collection')->insertGetId([
            'researcher_id' => $researcherId,
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'is_public'     => $data['is_public'] ?? false,
            'created_at'    => now(),
        ]);
    }

    public function getCollections(int $researcherId): array
    {
        $collections = DB::table('research_collection')
            ->where('researcher_id', $researcherId)
            ->orderBy('name')
            ->get()->toArray();

        foreach ($collections as &$col) {
            $col->item_count = (int) DB::table('research_collection_item')
                ->where('collection_id', $col->id)
                ->count();
        }

        return $collections;
    }

    public function getCollection(int $id): ?object
    {
        $collection = DB::table('research_collection')->where('id', $id)->first();

        if ($collection) {
            $collection->items = DB::table('research_collection_item')
                ->where('collection_id', $id)
                ->orderBy('created_at')
                ->get()->toArray();
        }

        return $collection;
    }

    public function updateCollection(int $id, array $data): bool
    {
        $allowed = ['name', 'description', 'is_public'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        return DB::table('research_collection')
            ->where('id', $id)
            ->update($updateData) >= 0;
    }

    public function deleteCollection(int $id): bool
    {
        DB::table('research_collection_item')->where('collection_id', $id)->delete();

        return DB::table('research_collection')->where('id', $id)->delete() > 0;
    }

    public function addToCollection(int $collectionId, int $objectId, ?string $notes = null): int
    {
        return DB::table('research_collection_item')->insertGetId([
            'collection_id' => $collectionId,
            'object_id'     => $objectId,
            'notes'         => $notes ?? '',
            'created_at'    => now(),
        ]);
    }

    public function removeFromCollection(int $collectionId, int $objectId): bool
    {
        return DB::table('research_collection_item')
            ->where('collection_id', $collectionId)
            ->where('object_id', $objectId)
            ->delete() > 0;
    }

    public function updateCollectionItemNotes(int $collectionId, int $objectId, string $notes): bool
    {
        return DB::table('research_collection_item')
            ->where('collection_id', $collectionId)
            ->where('object_id', $objectId)
            ->update(['notes' => $notes]) >= 0;
    }

    // =========================================================================
    // ANNOTATIONS
    // =========================================================================

    public function createAnnotation(array $data): int
    {
        $content = $this->sanitizeHtml($data['content'] ?? '');
        $validEntityTypes = ['information_object', 'actor', 'repository', 'accession', 'term'];
        $entityType = in_array($data['entity_type'] ?? '', $validEntityTypes)
            ? $data['entity_type']
            : 'information_object';
        $visibility = in_array($data['visibility'] ?? '', ['private', 'shared', 'public'])
            ? $data['visibility']
            : 'private';
        $contentFormat = in_array($data['content_format'] ?? '', ['text', 'html'])
            ? $data['content_format']
            : 'text';

        $annotationId = DB::table('research_annotation')->insertGetId([
            'researcher_id'  => $data['researcher_id'],
            'object_id'      => ((int) ($data['object_id'] ?? 0)) ?: null,
            'entity_type'    => $entityType,
            'collection_id'  => ((int) ($data['collection_id'] ?? 0)) ?: null,
            'title'          => trim($data['title'] ?? ''),
            'content'        => $content,
            'tags'           => trim($data['tags'] ?? '') ?: null,
            'content_format' => $contentFormat,
            'visibility'     => $visibility,
            'created_at'     => now(),
        ]);

        $this->logAudit('create', 'ResearchAnnotation', $annotationId, [], $data, $data['title'] ?? null);

        return $annotationId;
    }

    public function updateAnnotation(int $id, int $researcherId, array $data): bool
    {
        $validEntityTypes = ['information_object', 'actor', 'repository', 'accession', 'term'];
        $entityType = in_array($data['entity_type'] ?? '', $validEntityTypes)
            ? $data['entity_type']
            : 'information_object';
        $visibility = in_array($data['visibility'] ?? '', ['private', 'shared', 'public'])
            ? $data['visibility']
            : 'private';
        $contentFormat = in_array($data['content_format'] ?? '', ['text', 'html'])
            ? $data['content_format']
            : 'text';

        return DB::table('research_annotation')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->update([
                'title'          => trim($data['title'] ?? ''),
                'content'        => $this->sanitizeHtml($data['content'] ?? ''),
                'object_id'      => ((int) ($data['object_id'] ?? 0)) ?: null,
                'entity_type'    => $entityType,
                'collection_id'  => ((int) ($data['collection_id'] ?? 0)) ?: null,
                'tags'           => trim($data['tags'] ?? '') ?: null,
                'content_format' => $contentFormat,
                'visibility'     => $visibility,
            ]) >= 0;
    }

    public function getAnnotations(int $researcherId): array
    {
        return DB::table('research_annotation')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
    }

    public function getAnnotationsForEntity(string $entityIri, ?int $userId = null): array
    {
        $query = DB::table('research_annotation as ra')
            ->leftJoin('users', 'ra.researcher_id', '=', 'users.id')
            ->where('ra.entity_iri', $entityIri)
            ->select('ra.*', 'users.name as user_name');

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('ra.visibility', 'public')
                  ->orWhere('ra.researcher_id', $userId);
            });
        } else {
            $query->where('ra.visibility', 'public');
        }

        return $query->orderByDesc('ra.created_at')->get()->toArray();
    }

    public function searchAnnotations(int $researcherId, string $query): array
    {
        $pattern = '%' . $query . '%';

        return DB::table('research_annotation')
            ->where('researcher_id', $researcherId)
            ->where(function ($q) use ($pattern) {
                $q->where('title', 'ILIKE', $pattern)
                  ->orWhere('content', 'ILIKE', $pattern)
                  ->orWhere('tags', 'ILIKE', $pattern);
            })
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
    }

    public function deleteAnnotation(int $id, int $researcherId): bool
    {
        return DB::table('research_annotation')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->delete() > 0;
    }

    // =========================================================================
    // CITATIONS
    // =========================================================================

    public function generateCitation(string $entityIri, string $title, string $repository, string $identifier, string $style): array
    {
        $accessDate = date('j F Y');
        $url = config('app.url') . '/entities/' . urlencode($entityIri);

        return match ($style) {
            'chicago'  => ['citation' => "{$title}. {$identifier}. {$repository}. Accessed {$accessDate}. {$url}.", 'style' => 'Chicago'],
            'mla'      => ['citation' => "\"{$title}.\" {$repository}, {$identifier}. Web. {$accessDate}. <{$url}>.", 'style' => 'MLA'],
            'turabian' => ['citation' => "{$title}. {$identifier}. {$repository}. {$url}.", 'style' => 'Turabian'],
            'apa'      => ['citation' => "{$repository}. ({$accessDate}). {$title} [{$identifier}]. Retrieved from {$url}", 'style' => 'APA'],
            'harvard'  => ['citation' => "{$repository} ({$accessDate}) {$title} [{$identifier}]. Available at: {$url} (Accessed: {$accessDate}).", 'style' => 'Harvard'],
            'unisa'    => ['citation' => "{$repository}. {$title}. {$identifier}. [Online]. Available: {$url} [{$accessDate}].", 'style' => 'UNISA'],
            default    => ['citation' => "{$title}. {$identifier}. {$repository}. {$url}.", 'style' => $style],
        };
    }

    public function getCitations(string $entityIri, ?int $userId = null): array
    {
        $query = DB::table('research_citations as rc')
            ->leftJoin('users', 'rc.user_id', '=', 'users.id')
            ->where('rc.entity_iri', $entityIri)
            ->select('rc.*', 'users.name as user_name');

        if ($userId !== null) {
            $query->where('rc.user_id', $userId);
        }

        return $query->orderByDesc('rc.created_at')->get()->toArray();
    }

    public function addCitation(array $data): int
    {
        $citationId = DB::table('research_citations')->insertGetId([
            'user_id'        => $data['user_id'],
            'entity_iri'     => $data['entity_iri'],
            'citation_style' => $data['citation_style'],
            'citation_text'  => $data['citation_text'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->logAudit('create', 'ResearchCitation', $citationId, [], $data, null);

        return $citationId;
    }

    public function deleteCitation(int $citationId, int $userId): bool
    {
        return DB::table('research_citations')
            ->where('id', $citationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function logCitation(?int $researcherId, int $objectId, string $style, string $citation): void
    {
        try {
            DB::table('research_citation_log')->insert([
                'researcher_id'  => $researcherId,
                'object_id'      => $objectId,
                'citation_style' => $style,
                'citation_text'  => $citation,
                'created_at'     => now(),
            ]);
        } catch (\Exception $e) {
            // Citation logging is non-critical
        }
    }

    // =========================================================================
    // ASSESSMENTS
    // =========================================================================

    public function getAssessments(string $entityIri): array
    {
        return DB::table('research_assessments as ra')
            ->leftJoin('users', 'ra.user_id', '=', 'users.id')
            ->where('ra.entity_iri', $entityIri)
            ->select('ra.*', 'users.name as user_name')
            ->orderByDesc('ra.created_at')
            ->get()->toArray();
    }

    public function addAssessment(array $data): int
    {
        $assessmentId = DB::table('research_assessments')->insertGetId([
            'user_id'         => $data['user_id'],
            'entity_iri'      => $data['entity_iri'],
            'assessment_type' => $data['assessment_type'],
            'content'         => $this->sanitizeHtml($data['content']),
            'score'           => $data['score'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->logAudit('create', 'ResearchAssessment', $assessmentId, [], $data, null);

        return $assessmentId;
    }

    // =========================================================================
    // RESEARCHER TYPES
    // =========================================================================

    public function getResearcherTypes(): array
    {
        return DB::table('research_researcher_type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()->toArray();
    }

    public function getResearcherType(int $id): ?object
    {
        return DB::table('research_researcher_type')->where('id', $id)->first();
    }

    public function createResearcherType(array $data): int
    {
        $data['created_at'] = now();

        return DB::table('research_researcher_type')->insertGetId($data);
    }

    public function updateResearcherType(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('research_researcher_type')->where('id', $id)->update($data) > 0;
    }

    // =========================================================================
    // API KEYS
    // =========================================================================

    public function getApiKeys(int $researcherId): array
    {
        return DB::table('research_api_key')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
    }

    public function generateApiKey(int $researcherId, string $name, array $permissions = [], ?string $expiresAt = null): array
    {
        $key = 'rk_' . bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $key);

        DB::table('research_api_key')->insert([
            'researcher_id' => $researcherId,
            'name'          => $name,
            'key_hash'      => $keyHash,
            'key_prefix'    => substr($key, 0, 8),
            'permissions'   => json_encode($permissions),
            'expires_at'    => $expiresAt,
            'created_at'    => now(),
        ]);

        return ['key' => $key];
    }

    public function revokeApiKey(int $keyId, int $researcherId): bool
    {
        return DB::table('research_api_key')
            ->where('id', $keyId)
            ->where('researcher_id', $researcherId)
            ->update(['revoked_at' => now()]) > 0;
    }

    // =========================================================================
    // NOTIFICATIONS
    // =========================================================================

    public function getNotifications(int $researcherId, int $limit = 100): array
    {
        return DB::table('research_notification')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()->toArray();
    }

    public function markNotificationRead(int $id, int $researcherId): bool
    {
        return DB::table('research_notification')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->update(['is_read' => true, 'read_at' => now()]) > 0;
    }

    public function markAllNotificationsRead(int $researcherId): int
    {
        return DB::table('research_notification')
            ->where('researcher_id', $researcherId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function getUnreadNotificationCount(int $researcherId): int
    {
        return (int) DB::table('research_notification')
            ->where('researcher_id', $researcherId)
            ->where('is_read', false)
            ->count();
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function getDashboardStats(?int $userId = null): array
    {
        if ($userId !== null) {
            $researcher = $this->getResearcherByUserId($userId);
            if ($researcher) {
                return [
                    'workspace_count'  => (int) DB::table('research_workspaces')->where('user_id', $userId)->count(),
                    'annotation_count' => (int) DB::table('research_annotation')->where('researcher_id', $researcher->id)->count(),
                    'citation_count'   => (int) DB::table('research_citations')->where('user_id', $userId)->count(),
                    'collection_count' => (int) DB::table('research_collection')->where('researcher_id', $researcher->id)->count(),
                    'project_count'    => (int) DB::table('research_project_collaborator')
                        ->where('researcher_id', $researcher->id)
                        ->where('status', 'accepted')->count(),
                    'assessment_count' => (int) DB::table('research_assessments')->where('user_id', $userId)->count(),
                ];
            }
        }

        return [
            'total_researchers' => (int) DB::table('research_researcher')->where('status', 'approved')->count(),
            'today_bookings'    => (int) DB::table('research_booking')
                ->where('booking_date', date('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])->count(),
            'week_bookings'     => (int) DB::table('research_booking')
                ->whereBetween('booking_date', [date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))])
                ->whereIn('status', ['pending', 'confirmed'])->count(),
            'pending_requests'  => (int) DB::table('research_researcher')->where('status', 'pending')->count(),
        ];
    }

    public function getEnhancedDashboardData(int $researcherId): array
    {
        $data = [];

        $data['unread_notifications'] = $this->getUnreadNotificationCount($researcherId);

        $data['recent_activity'] = DB::table('research_activity_log')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()->toArray();

        $data['upcoming_bookings'] = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $researcherId)
            ->where('b.booking_date', '>=', date('Y-m-d'))
            ->whereIn('b.status', ['pending', 'confirmed'])
            ->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date')
            ->limit(5)
            ->get()->toArray();

        $data['recent_collections'] = DB::table('research_collection')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()->toArray();

        $data['recent_journal'] = DB::table('research_journal_entry')
            ->where('researcher_id', $researcherId)
            ->orderBy('entry_date', 'desc')
            ->limit(5)
            ->get()->toArray();

        return $data;
    }

    public function getAdminStatistics(string $dateFrom, string $dateTo): array
    {
        return [
            'total_researchers'    => (int) DB::table('research_researcher')->count(),
            'approved_researchers' => (int) DB::table('research_researcher')->where('status', 'approved')->count(),
            'total_bookings'       => (int) DB::table('research_booking')
                ->whereBetween('booking_date', [$dateFrom, $dateTo])->count(),
            'completed_bookings'   => (int) DB::table('research_booking')
                ->where('status', 'completed')
                ->whereBetween('booking_date', [$dateFrom, $dateTo])->count(),
            'total_collections'    => (int) DB::table('research_collection')->count(),
            'total_annotations'    => (int) DB::table('research_annotation')->count(),
            'total_projects'       => (int) DB::table('research_project')->count(),
            'total_reports'        => (int) DB::table('research_report')->count(),
        ];
    }

    // =========================================================================
    // WORKSPACES (basic — expanded version in CollaborationService)
    // =========================================================================

    public function createWorkspace(array $data): int
    {
        $workspaceId = DB::table('research_workspaces')->insertGetId([
            'user_id'     => $data['user_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_public'   => $data['is_public'] ?? false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->logAudit('create', 'ResearchWorkspace', $workspaceId, [], $data, null);

        return $workspaceId;
    }

    public function getWorkspaces(int $userId): array
    {
        return DB::table('research_workspaces')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get()->toArray();
    }

    public function getWorkspace(int $workspaceId): ?object
    {
        $workspace = DB::table('research_workspaces')
            ->where('id', $workspaceId)
            ->first();

        if ($workspace) {
            $workspace->items = DB::table('research_workspace_items')
                ->where('workspace_id', $workspaceId)
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get()->toArray();
        }

        return $workspace;
    }

    public function addItemToWorkspace(int $workspaceId, array $data): int
    {
        $maxSort = (int) DB::table('research_workspace_items')
            ->where('workspace_id', $workspaceId)
            ->max('sort_order');

        $itemId = DB::table('research_workspace_items')->insertGetId([
            'workspace_id' => $workspaceId,
            'entity_iri'   => $data['entity_iri'],
            'entity_type'  => $data['entity_type'],
            'title'        => $data['title'],
            'sort_order'   => $data['sort_order'] ?? ($maxSort + 1),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->logAudit('add_item', 'ResearchWorkspaceItem', $itemId, [], $data, null);

        return $itemId;
    }

    public function removeItemFromWorkspace(int $workspaceId, string $entityIri): bool
    {
        return DB::table('research_workspace_items')
            ->where('workspace_id', $workspaceId)
            ->where('entity_iri', $entityIri)
            ->delete() > 0;
    }

    // =========================================================================
    // INSTITUTIONS
    // =========================================================================

    public function getInstitutions(): array
    {
        return DB::table('research_institution')
            ->orderBy('name')
            ->get()->toArray();
    }

    // =========================================================================
    // ACTIVITIES
    // =========================================================================

    public function getRecentActivities(int $limit = 50): array
    {
        return DB::table('research_activity_log')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()->toArray();
    }

    // =========================================================================
    // HTML SANITIZATION
    // =========================================================================

    public function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><a><blockquote><code><pre><hr><table><thead><tbody><tr><th><td><img><span><div><sub><sup>';
        $html = strip_tags($html, $allowed);
        $html = (string) preg_replace('/\bon\w+\s*=/i', 'data-removed=', $html);
        $html = (string) preg_replace('/javascript\s*:/i', '', $html);

        return $html;
    }

    // =========================================================================
    // SEARCH ITEMS (AJAX)
    // =========================================================================

    public function searchItems(string $query, int $limit = 20): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $pattern = '%' . $query . '%';

        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', '!=', 1)
            ->where(function ($q) use ($pattern) {
                $q->where('ioi.title', 'ILIKE', $pattern)
                  ->orWhere('io.identifier', 'ILIKE', $pattern);
            })
            ->select('io.id', 'io.identifier', 'ioi.title', 's.slug')
            ->orderBy('ioi.title')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->title ?: 'Untitled [' . $item->id . ']',
                'identifier' => $item->identifier,
                'slug'       => $item->slug,
            ])
            ->toArray();
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    protected function logAudit(string $action, string $objectType, int $objectId, array $oldValues, array $newValues, ?string $description): void
    {
        try {
            DB::table('audit_log')->insert([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'entity_type' => $objectType,
                'entity_id'   => (string) $objectId,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            // Audit logging is non-critical
        }
    }
}
