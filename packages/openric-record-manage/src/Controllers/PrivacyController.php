<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\AiNerServiceInterface;
use OpenRiC\RecordManage\Contracts\PrivacyServiceInterface;

/**
 * Privacy controller — PII scanning, redaction, dashboard.
 * Adapted from Heratio PrivacyController (216 lines).
 */
class PrivacyController extends Controller
{
    public function __construct(
        private readonly PrivacyServiceInterface $privacyService,
        private readonly AiNerServiceInterface $nerService,
    ) {}

    /**
     * Scan a record for PII using NER entities.
     */
    public function scan(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $allEntities = $this->nerService->getEntitiesForRecord($recordId);

        $piiRiskMap = [
            'SA_ID' => 'high', 'PASSPORT' => 'high', 'BANK' => 'high', 'TAX' => 'high',
            'MEDICAL' => 'high', 'BIOMETRIC' => 'high', 'EMAIL' => 'medium', 'PHONE' => 'medium',
            'DOB' => 'medium', 'ADDRESS' => 'low', 'NAME' => 'low', 'IP_ADDRESS' => 'low', 'PERSON' => 'low',
        ];

        $piiEntities = [];
        foreach ($allEntities as $entity) {
            $risk = $piiRiskMap[$entity->entity_type] ?? null;
            if ($risk !== null) {
                $piiEntities[] = (object) [
                    'type' => $entity->entity_type, 'value' => $entity->entity_value,
                    'confidence' => (float) $entity->confidence, 'risk' => $risk, 'source' => 'NER extraction',
                ];
            }
        }

        $highCount = count(array_filter($piiEntities, fn ($e) => $e->risk === 'high'));
        $medCount  = count(array_filter($piiEntities, fn ($e) => $e->risk === 'medium'));
        $lowCount  = count(array_filter($piiEntities, fn ($e) => $e->risk === 'low'));
        $riskScore = min(100, ($highCount * 30) + ($medCount * 15) + ($lowCount * 5));

        $scanResult = (object) [
            'entities'       => $piiEntities,
            'risk_score'     => $riskScore,
            'fields_scanned' => ['title', 'scope_and_content', 'archival_history'],
        ];

        return view('record-manage::privacy.scan', [
            'record'     => $record,
            'scanResult' => $scanResult,
        ]);
    }

    /**
     * Visual redaction tool.
     */
    public function redaction(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $existingRedactions = $this->privacyService->getRedactions($recordId);
        $redactionRegions = $existingRedactions->map(function ($r) {
            $coords = is_string($r->coordinates ?? null) ? json_decode($r->coordinates, true) : (array) ($r->coordinates ?? []);
            return [
                'id' => $r->id, 'left' => $coords['left'] ?? 0, 'top' => $coords['top'] ?? 0,
                'width' => $coords['width'] ?? 100, 'height' => $coords['height'] ?? 50,
                'page' => $r->page_number ?? 1, 'label' => $r->label ?? '', 'status' => $r->status ?? 'pending',
            ];
        })->values()->toArray();

        return view('record-manage::privacy.redaction', [
            'record'             => $record,
            'existingRedactions' => $redactionRegions,
        ]);
    }

    /**
     * Privacy dashboard.
     */
    public function dashboard(): View
    {
        $stats = $this->privacyService->getDashboardStats();

        return view('record-manage::privacy.dashboard', [
            'stats' => $stats,
        ]);
    }
}
