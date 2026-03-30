<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\RecordManage\Contracts\ProvenanceServiceInterface;

/**
 * Service for provenance chain operations.
 * Adapted from Heratio ProvenanceService (308 lines).
 *
 * Table: provenance_entries
 */
class ProvenanceService implements ProvenanceServiceInterface
{
    public function getChain(int $recordId): Collection
    {
        try {
            return DB::table('provenance_entries')
                ->where('record_id', $recordId)
                ->orderBy('sequence', 'asc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getEntry(int $id): ?object
    {
        try {
            return DB::table('provenance_entries')
                ->where('id', $id)
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }

    public function createEntry(array $data): int
    {
        $maxSeq = DB::table('provenance_entries')
            ->where('record_id', $data['record_id'])
            ->max('sequence') ?? 0;

        return DB::table('provenance_entries')->insertGetId([
            'record_id'            => $data['record_id'],
            'sequence'             => $maxSeq + 1,
            'owner_name'           => $data['owner_name'],
            'owner_type'           => $data['owner_type'] ?? 'unknown',
            'owner_agent_id'       => $data['owner_agent_id'] ?? null,
            'owner_location'       => $data['owner_location'] ?? null,
            'owner_location_tgn'   => $data['owner_location_tgn'] ?? null,
            'start_date'           => $data['start_date'] ?? null,
            'start_date_qualifier' => $data['start_date_qualifier'] ?? null,
            'end_date'             => $data['end_date'] ?? null,
            'end_date_qualifier'   => $data['end_date_qualifier'] ?? null,
            'transfer_type'        => $data['transfer_type'] ?? 'unknown',
            'transfer_details'     => $data['transfer_details'] ?? null,
            'sale_price'           => $data['sale_price'] ?? null,
            'sale_currency'        => $data['sale_currency'] ?? null,
            'auction_house'        => $data['auction_house'] ?? null,
            'auction_lot'          => $data['auction_lot'] ?? null,
            'certainty'            => $data['certainty'] ?? 'unknown',
            'sources'              => $data['sources'] ?? null,
            'notes'                => $data['notes'] ?? null,
            'is_gap'               => $data['is_gap'] ?? false,
            'gap_explanation'      => $data['gap_explanation'] ?? null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    public function updateEntry(int $id, array $data): bool
    {
        $update = [];
        $fields = [
            'owner_name', 'owner_type', 'owner_agent_id', 'owner_location',
            'owner_location_tgn', 'start_date', 'start_date_qualifier',
            'end_date', 'end_date_qualifier', 'transfer_type', 'transfer_details',
            'sale_price', 'sale_currency', 'auction_house', 'auction_lot',
            'certainty', 'sources', 'notes', 'is_gap', 'gap_explanation', 'sequence',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        $update['updated_at'] = now();

        return DB::table('provenance_entries')
            ->where('id', $id)
            ->update($update) >= 0;
    }

    public function deleteEntry(int $id): bool
    {
        $entry = $this->getEntry($id);
        if (!$entry) {
            return false;
        }

        $deleted = DB::table('provenance_entries')
            ->where('id', $id)
            ->delete() > 0;

        if ($deleted) {
            $this->resequence($entry->record_id);
        }

        return $deleted;
    }

    private function resequence(int $recordId): void
    {
        $entries = DB::table('provenance_entries')
            ->where('record_id', $recordId)
            ->orderBy('sequence', 'asc')
            ->select('id')
            ->get();

        foreach ($entries as $i => $entry) {
            DB::table('provenance_entries')
                ->where('id', $entry->id)
                ->update(['sequence' => $i + 1]);
        }
    }

    public function getTimelineData(int $recordId): string
    {
        $chain = $this->getChain($recordId);
        $timeline = [];

        foreach ($chain as $entry) {
            $startDate = $entry->start_date;
            $dateDisplay = $startDate ?: 'Unknown date';
            if ($entry->start_date_qualifier) {
                $dateDisplay = $entry->start_date_qualifier . ' ' . $dateDisplay;
            }

            $timeline[] = [
                'id'          => $entry->id,
                'type'        => $this->getTransferTypeLabel($entry->transfer_type),
                'label'       => $entry->owner_name,
                'startDate'   => $startDate,
                'endDate'     => $entry->end_date,
                'description' => $entry->notes ?? $entry->transfer_details ?? '',
                'category'    => $this->categorizeTransferType($entry->transfer_type),
                'certainty'   => $entry->certainty,
                'from'        => null,
                'to'          => $entry->owner_name,
                'location'    => $entry->owner_location,
            ];
        }

        return json_encode($timeline);
    }

    public function getTransferTypes(): array
    {
        return [
            'Ownership Changes' => [
                'sale' => 'Sale', 'purchase' => 'Purchase', 'auction' => 'Auction Sale',
                'gift' => 'Gift', 'donation' => 'Donation', 'bequest' => 'Bequest',
                'inheritance' => 'Inheritance', 'descent' => 'By Descent',
                'transfer' => 'Transfer', 'exchange' => 'Exchange',
            ],
            'Loans & Deposits' => [
                'loan_out' => 'Loan Out', 'loan_return' => 'Loan Return',
                'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal',
            ],
            'Creation & Discovery' => [
                'creation' => 'Creation', 'commission' => 'Commission',
                'discovery' => 'Discovery', 'excavation' => 'Excavation',
            ],
            'Loss & Recovery' => [
                'theft' => 'Theft', 'recovery' => 'Recovery',
                'confiscation' => 'Confiscation', 'restitution' => 'Restitution',
                'repatriation' => 'Repatriation',
            ],
            'Institutional' => [
                'accessioning' => 'Accessioning', 'deaccessioning' => 'Deaccessioning',
            ],
            'Other' => [
                'unknown' => 'Unknown', 'other' => 'Other',
            ],
        ];
    }

    public function getOwnerTypes(): array
    {
        return [
            'person' => 'Person', 'family' => 'Family',
            'organization' => 'Organization', 'institution' => 'Institution',
            'dealer' => 'Dealer', 'auction_house' => 'Auction House',
            'government' => 'Government', 'unknown' => 'Unknown',
        ];
    }

    public function getCertaintyLevels(): array
    {
        return [
            'certain'   => 'Certain - Documented evidence',
            'probable'  => 'Probable - Strong circumstantial evidence',
            'possible'  => 'Possible - Some supporting evidence',
            'uncertain' => 'Uncertain - Limited evidence',
            'unknown'   => 'Unknown - No evidence',
        ];
    }

    private function getTransferTypeLabel(string $type): string
    {
        $flat = [];
        foreach ($this->getTransferTypes() as $group) {
            $flat = array_merge($flat, $group);
        }
        return $flat[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    private function categorizeTransferType(string $type): string
    {
        $type = strtolower($type);

        if (in_array($type, ['creation', 'commission', 'discovery', 'excavation'])) {
            return 'creation';
        }
        if (in_array($type, ['sale', 'purchase'])) {
            return 'sale';
        }
        if (in_array($type, ['gift', 'donation'])) {
            return 'gift';
        }
        if (in_array($type, ['bequest', 'inheritance', 'descent'])) {
            return 'inheritance';
        }
        if ($type === 'auction') {
            return 'auction';
        }
        if (in_array($type, ['transfer', 'exchange', 'accessioning', 'deaccessioning'])) {
            return 'transfer';
        }
        if (in_array($type, ['loan_out', 'loan_return', 'deposit', 'withdrawal'])) {
            return 'loan';
        }
        if (in_array($type, ['theft', 'confiscation'])) {
            return 'theft';
        }
        if (in_array($type, ['recovery', 'restitution', 'repatriation'])) {
            return 'recovery';
        }

        return 'event';
    }
}
