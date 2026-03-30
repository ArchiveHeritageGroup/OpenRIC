<?php

declare(strict_types=1);

namespace OpenRiC\Research\Services;

use Illuminate\Support\Facades\DB;

/**
 * ReproductionService -- Reproduction Request Management.
 *
 * Handles reproduction requests, items, files, cost calculation, status tracking,
 * payment recording, and statistics.
 *
 * Adapted from Heratio AhgResearch\Services\ReproductionService.
 * PostgreSQL ILIKE used for all text searches.
 */
class ReproductionService
{
    private array $defaultPricing = [
        'photocopy'      => ['base' => 2.00, 'per_page' => 0.50],
        'scan'           => ['base' => 5.00, 'per_page' => 1.00],
        'photograph'     => ['base' => 15.00, 'per_image' => 5.00],
        'digital_copy'   => ['base' => 10.00, 'per_file' => 2.00],
        'transcription'  => ['base' => 50.00, 'per_page' => 25.00],
        'certification'  => ['base' => 25.00, 'per_document' => 10.00],
    ];

    // =========================================================================
    // REQUEST MANAGEMENT
    // =========================================================================

    public function createRequest(int $researcherId, array $data): int
    {
        $referenceNumber = $this->generateReferenceNumber();

        return DB::table('research_reproduction_request')->insertGetId([
            'researcher_id'      => $researcherId,
            'reference_number'   => $referenceNumber,
            'purpose'            => $data['purpose'] ?? null,
            'intended_use'       => $data['intended_use'] ?? 'personal',
            'publication_details' => $data['publication_details'] ?? null,
            'status'             => 'draft',
            'currency'           => $data['currency'] ?? 'ZAR',
            'delivery_method'    => $data['delivery_method'] ?? 'email',
            'delivery_address'   => $data['delivery_address'] ?? null,
            'delivery_email'     => $data['delivery_email'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function generateReferenceNumber(): string
    {
        $year = date('Y');
        $prefix = "REP-{$year}-";

        $lastRef = DB::table('research_reproduction_request')
            ->where('reference_number', 'ILIKE', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->value('reference_number');

        if ($lastRef) {
            $lastNum = (int) substr($lastRef, -5);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . str_pad((string) $newNum, 5, '0', STR_PAD_LEFT);
    }

    public function getRequest(int $requestId): ?object
    {
        $request = DB::table('research_reproduction_request as r')
            ->leftJoin('research_researcher as res', 'r.researcher_id', '=', 'res.id')
            ->where('r.id', $requestId)
            ->select(
                'r.*',
                'res.first_name',
                'res.last_name',
                'res.email as researcher_email',
                'res.institution'
            )
            ->first();

        if ($request) {
            $request->items = $this->getItems($requestId);
            $request->status_history = $this->getStatusHistory($requestId, 'reproduction');
        }

        return $request;
    }

    public function getRequests(int $researcherId, array $filters = []): array
    {
        $query = DB::table('research_reproduction_request')
            ->where('researcher_id', $researcherId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $requests = $query->orderBy('created_at', 'desc')->get()->toArray();

        foreach ($requests as &$request) {
            $request->item_count = (int) DB::table('research_reproduction_item')
                ->where('request_id', $request->id)
                ->count();
        }

        return $requests;
    }

    public function getAllRequests(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('research_reproduction_request as r')
            ->join('research_researcher as res', 'r.researcher_id', '=', 'res.id')
            ->select(
                'r.*',
                'res.first_name',
                'res.last_name',
                'res.email as researcher_email',
                'res.institution'
            );

        if (!empty($filters['status'])) {
            $query->where('r.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('r.reference_number', 'ILIKE', $search)
                    ->orWhere('res.first_name', 'ILIKE', $search)
                    ->orWhere('res.last_name', 'ILIKE', $search)
                    ->orWhere('res.email', 'ILIKE', $search);
            });
        }

        return $query->orderBy('r.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    public function updateRequest(int $requestId, array $data): bool
    {
        $allowed = [
            'purpose', 'intended_use', 'publication_details', 'estimated_cost',
            'final_cost', 'currency', 'payment_reference', 'payment_date',
            'payment_method', 'invoice_number', 'invoice_date', 'delivery_method',
            'delivery_address', 'delivery_email', 'notes', 'admin_notes',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));
        $updateData['updated_at'] = now();

        return DB::table('research_reproduction_request')
            ->where('id', $requestId)
            ->update($updateData) >= 0;
    }

    public function submitRequest(int $requestId): array
    {
        $request = DB::table('research_reproduction_request')
            ->where('id', $requestId)
            ->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }
        if ($request->status !== 'draft') {
            return ['success' => false, 'error' => 'Request has already been submitted'];
        }

        $itemCount = (int) DB::table('research_reproduction_item')
            ->where('request_id', $requestId)
            ->count();

        if ($itemCount === 0) {
            return ['success' => false, 'error' => 'Cannot submit request with no items'];
        }

        $estimatedCost = $this->calculateCosts($requestId);
        $this->updateStatus($requestId, 'submitted', null, 'Request submitted by researcher');

        DB::table('research_reproduction_request')
            ->where('id', $requestId)
            ->update([
                'estimated_cost' => $estimatedCost,
                'updated_at'     => now(),
            ]);

        return ['success' => true, 'estimated_cost' => $estimatedCost];
    }

    public function updateStatus(int $requestId, string $newStatus, ?int $changedBy = null, ?string $notes = null): bool
    {
        $request = DB::table('research_reproduction_request')
            ->where('id', $requestId)
            ->first();

        if (!$request) {
            return false;
        }

        $oldStatus = $request->status;

        DB::table('research_request_status_history')->insert([
            'request_id'   => $requestId,
            'request_type' => 'reproduction',
            'old_status'   => $oldStatus,
            'new_status'   => $newStatus,
            'changed_by'   => $changedBy,
            'notes'        => $notes,
            'created_at'   => now(),
        ]);

        $updateData = [
            'status'     => $newStatus,
            'updated_at' => now(),
        ];

        if ($newStatus === 'completed') {
            $updateData['completed_at'] = now();
        }
        if ($changedBy && in_array($newStatus, ['processing', 'in_production', 'completed'], true)) {
            $updateData['processed_by'] = $changedBy;
        }

        $updated = DB::table('research_reproduction_request')
            ->where('id', $requestId)
            ->update($updateData) > 0;

        if ($updated) {
            try {
                event('research.reproduction.status_changed', [
                    'object_id'    => $requestId,
                    'object_type'  => 'research_reproduction_request',
                    'performed_by' => $changedBy ?? 0,
                    'from_status'  => $oldStatus,
                    'to_status'    => $newStatus,
                    'comment'      => $notes ?? "Status: {$oldStatus} -> {$newStatus}",
                ]);
            } catch (\Exception $e) {
                // Workflow plugin may not be installed
            }
        }

        return $updated;
    }

    public function getStatusHistory(int $requestId, string $requestType = 'reproduction'): array
    {
        return DB::table('research_request_status_history as h')
            ->leftJoin('users as u', 'h.changed_by', '=', 'u.id')
            ->where('h.request_id', $requestId)
            ->where('h.request_type', $requestType)
            ->select('h.*', 'u.name as changed_by_name')
            ->orderBy('h.created_at', 'desc')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // ITEMS
    // =========================================================================

    public function addItem(int $requestId, array $data): int
    {
        return DB::table('research_reproduction_item')->insertGetId([
            'request_id'           => $requestId,
            'object_id'            => $data['object_id'],
            'digital_object_id'    => $data['digital_object_id'] ?? null,
            'reproduction_type'    => $data['reproduction_type'] ?? 'scan',
            'format'               => $data['format'] ?? 'PDF',
            'resolution'           => $data['resolution'] ?? null,
            'color_mode'           => $data['color_mode'] ?? 'grayscale',
            'quantity'             => $data['quantity'] ?? 1,
            'page_range'           => $data['page_range'] ?? null,
            'special_instructions' => $data['special_instructions'] ?? null,
            'status'               => 'pending',
            'notes'                => $data['notes'] ?? null,
            'created_at'           => now(),
        ]);
    }

    public function removeItem(int $itemId): bool
    {
        $files = DB::table('research_reproduction_file')
            ->where('item_id', $itemId)
            ->get();

        foreach ($files as $file) {
            if (file_exists($file->file_path)) {
                unlink($file->file_path);
            }
        }

        DB::table('research_reproduction_file')->where('item_id', $itemId)->delete();

        return DB::table('research_reproduction_item')->where('id', $itemId)->delete() > 0;
    }

    public function getItems(int $requestId): array
    {
        $items = DB::table('research_reproduction_item as i')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('i.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'i.object_id', '=', 'slug.object_id')
            ->where('i.request_id', $requestId)
            ->select('i.*', 'ioi.title as object_title', 'slug.slug')
            ->get()
            ->toArray();

        foreach ($items as &$item) {
            $item->files = DB::table('research_reproduction_file')
                ->where('item_id', $item->id)
                ->get()
                ->toArray();
        }

        return $items;
    }

    public function updateItem(int $itemId, array $data): bool
    {
        $allowed = [
            'reproduction_type', 'format', 'resolution', 'color_mode',
            'quantity', 'page_range', 'special_instructions', 'unit_price',
            'total_price', 'status', 'notes',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));

        return DB::table('research_reproduction_item')
            ->where('id', $itemId)
            ->update($updateData) >= 0;
    }

    public function completeItem(int $itemId): bool
    {
        return DB::table('research_reproduction_item')
            ->where('id', $itemId)
            ->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]) > 0;
    }

    // =========================================================================
    // FILES
    // =========================================================================

    public function uploadFile(int $itemId, array $fileData): int
    {
        $downloadToken = bin2hex(random_bytes(32));
        $downloadExpires = date('Y-m-d H:i:s', strtotime('+30 days'));

        return DB::table('research_reproduction_file')->insertGetId([
            'item_id'              => $itemId,
            'file_name'            => $fileData['file_name'],
            'file_path'            => $fileData['file_path'],
            'file_size'            => $fileData['file_size'] ?? null,
            'mime_type'            => $fileData['mime_type'] ?? null,
            'checksum'             => $fileData['checksum'] ?? null,
            'download_expires_at'  => $downloadExpires,
            'download_token'       => $downloadToken,
            'created_at'           => now(),
        ]);
    }

    public function getFiles(int $itemId): array
    {
        return DB::table('research_reproduction_file')
            ->where('item_id', $itemId)
            ->get()
            ->toArray();
    }

    public function getFileByToken(string $token): ?object
    {
        return DB::table('research_reproduction_file')
            ->where('download_token', $token)
            ->where(function ($q) {
                $q->whereNull('download_expires_at')
                    ->orWhere('download_expires_at', '>', now());
            })
            ->first();
    }

    public function recordDownload(int $fileId): bool
    {
        return DB::table('research_reproduction_file')
            ->where('id', $fileId)
            ->update([
                'download_count' => DB::raw('download_count + 1'),
            ]) > 0;
    }

    public function deleteFile(int $fileId): bool
    {
        $file = DB::table('research_reproduction_file')
            ->where('id', $fileId)
            ->first();

        if ($file && file_exists($file->file_path)) {
            unlink($file->file_path);
        }

        return DB::table('research_reproduction_file')
            ->where('id', $fileId)
            ->delete() > 0;
    }

    // =========================================================================
    // COST CALCULATION
    // =========================================================================

    public function calculateCosts(int $requestId): float
    {
        $items = $this->getItems($requestId);
        $pricing = $this->getPricing();
        $totalCost = 0.0;

        foreach ($items as $item) {
            $type = $item->reproduction_type;
            $typePricing = $pricing[$type] ?? $pricing['scan'];

            $baseCost = $typePricing['base'] ?? 0;
            $quantity = $item->quantity ?? 1;

            $perUnit = 0;
            if (isset($typePricing['per_page'])) {
                $perUnit = $typePricing['per_page'];
            } elseif (isset($typePricing['per_image'])) {
                $perUnit = $typePricing['per_image'];
            } elseif (isset($typePricing['per_file'])) {
                $perUnit = $typePricing['per_file'];
            } elseif (isset($typePricing['per_document'])) {
                $perUnit = $typePricing['per_document'];
            }

            $itemCost = $baseCost + ($perUnit * $quantity);

            if (($item->color_mode ?? '') === 'color') {
                $itemCost *= 1.5;
            }
            if ($item->resolution && preg_match('/(\d+)/', $item->resolution, $matches)) {
                $dpi = (int) $matches[1];
                if ($dpi >= 600) {
                    $itemCost *= 1.25;
                }
            }

            DB::table('research_reproduction_item')
                ->where('id', $item->id)
                ->update([
                    'unit_price'  => $perUnit,
                    'total_price' => $itemCost,
                ]);

            $totalCost += $itemCost;
        }

        return round($totalCost, 2);
    }

    public function getPricing(): array
    {
        $customPricing = config('research.reproduction_pricing', null);

        if ($customPricing && is_array($customPricing)) {
            return array_merge($this->defaultPricing, $customPricing);
        }

        return $this->defaultPricing;
    }

    public function generateInvoiceNumber(int $requestId): string
    {
        $year = date('Y');
        $month = date('m');

        return "INV-{$year}{$month}-" . str_pad((string) $requestId, 5, '0', STR_PAD_LEFT);
    }

    public function recordPayment(int $requestId, array $paymentData): bool
    {
        $updated = DB::table('research_reproduction_request')
            ->where('id', $requestId)
            ->update([
                'payment_reference' => $paymentData['reference'] ?? null,
                'payment_date'      => $paymentData['date'] ?? date('Y-m-d'),
                'payment_method'    => $paymentData['method'] ?? null,
                'final_cost'        => $paymentData['amount'] ?? null,
                'updated_at'        => now(),
            ]) > 0;

        if ($updated) {
            $this->updateStatus($requestId, 'in_production', null, 'Payment recorded');
        }

        return $updated;
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    public function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');

        $query = DB::table('research_reproduction_request')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $revenue = (clone $query)
            ->where('status', 'completed')
            ->sum('final_cost');

        $byType = DB::table('research_reproduction_item as i')
            ->join('research_reproduction_request as r', 'i.request_id', '=', 'r.id')
            ->whereBetween('r.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('i.reproduction_type, COUNT(*) as count')
            ->groupBy('i.reproduction_type')
            ->pluck('count', 'reproduction_type')
            ->toArray();

        return [
            'date_range'     => ['from' => $dateFrom, 'to' => $dateTo],
            'total_requests' => array_sum($byStatus),
            'by_status'      => $byStatus,
            'by_type'        => $byType,
            'total_revenue'  => (float) $revenue,
            'pending_count'  => (int) (($byStatus['submitted'] ?? 0) + ($byStatus['processing'] ?? 0)),
        ];
    }
}
