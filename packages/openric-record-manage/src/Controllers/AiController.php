<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\AiNerServiceInterface;

/**
 * AI controller — NER extraction, review, summarize, translate.
 * Adapted from Heratio AiController (130 lines).
 */
class AiController extends Controller
{
    public function __construct(
        private readonly AiNerServiceInterface $nerService,
    ) {}

    /**
     * Extract named entities from a record's description text.
     */
    public function extract(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $entities          = $this->nerService->getEntitiesForRecord($recordId);
        $entityLinks       = $this->nerService->getEntityLinks($recordId);
        $extractionHistory = $this->nerService->getExtractionHistory($recordId);

        return view('record-manage::ai.extract', [
            'record'            => $record,
            'entities'          => $entities,
            'entityLinks'       => $entityLinks,
            'extractionHistory' => $extractionHistory,
        ]);
    }

    /**
     * NER Review dashboard.
     */
    public function review(): View
    {
        $pending = $this->nerService->getPendingExtractions();

        return view('record-manage::ai.review', [
            'pending' => $pending,
        ]);
    }

    /**
     * Generate summary for a record.
     */
    public function summarize(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        return view('record-manage::ai.summarize', ['record' => $record]);
    }

    /**
     * Translate a record's description.
     */
    public function translate(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        return view('record-manage::ai.translate', ['record' => $record]);
    }
}
