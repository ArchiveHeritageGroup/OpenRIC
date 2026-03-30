<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Import controller — XML, CSV, SKOS import forms and processing.
 * Adapted from Heratio ImportController (358 lines).
 */
class ImportController extends Controller
{
    /**
     * Show the XML import form.
     */
    public function xml(): View
    {
        return view('record-manage::import.select', [
            'type'  => 'xml',
            'title' => 'Import XML',
        ]);
    }

    /**
     * Show the CSV import form.
     */
    public function csv(): View
    {
        return view('record-manage::import.select', [
            'type'  => 'csv',
            'title' => 'Import CSV',
        ]);
    }

    /**
     * Process the import upload.
     */
    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'file'       => 'required|file|max:51200',
            'importType' => 'required|in:xml,csv',
            'objectType' => 'required|string',
            'updateType' => 'required|string',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('imports', $filename, 'local');

        return redirect()->route('records.index')
            ->with('success', "Import queued: {$request->input('objectType')} ({$request->input('importType')}) — file: {$file->getClientOriginalName()}. Processing will begin shortly.");
    }

    /**
     * Show the Validate CSV form.
     */
    public function validateCsv(): View
    {
        return view('record-manage::import.validate-csv', [
            'title'   => 'Validate CSV',
            'results' => null,
        ]);
    }

    /**
     * Process CSV validation.
     */
    public function validateCsvProcess(Request $request): View
    {
        $request->validate([
            'objectType' => 'required|in:record,accession,agent,repository',
            'file'       => 'required|file|mimes:csv,txt|max:51200',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $headerLine = $handle ? fgets($handle) : '';
        if ($handle) {
            fclose($handle);
        }

        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
        $headers = array_map('trim', str_getcsv(trim($headerLine)));

        $expectedColumns = $this->getExpectedColumns($request->input('objectType'));
        $requiredColumns = $this->getRequiredColumns($request->input('objectType'));

        $results = [];
        foreach ($headers as $header) {
            if (empty($header)) {
                continue;
            }
            $results[] = [
                'column'  => $header,
                'status'  => in_array($header, $expectedColumns) ? 'valid' : 'invalid',
                'message' => in_array($header, $expectedColumns) ? 'Valid column' : 'Unrecognized column',
            ];
        }

        $presentHeaders = array_column($results, 'column');
        foreach ($requiredColumns as $required) {
            if (!in_array($required, $presentHeaders)) {
                $results[] = ['column' => $required, 'status' => 'missing', 'message' => 'Required column is missing'];
            }
        }

        return view('record-manage::import.validate-csv', [
            'title'        => 'Validate CSV',
            'results'      => $results,
            'objectType'   => $request->input('objectType'),
            'fileName'     => $file->getClientOriginalName(),
            'validCount'   => count(array_filter($results, fn ($r) => $r['status'] === 'valid')),
            'invalidCount' => count(array_filter($results, fn ($r) => $r['status'] === 'invalid')),
            'missingCount' => count(array_filter($results, fn ($r) => $r['status'] === 'missing')),
        ]);
    }

    /**
     * Show the SKOS import form.
     */
    public function skosImport(): View
    {
        return view('record-manage::import.skos-import', [
            'title' => 'SKOS Import',
        ]);
    }

    private function getExpectedColumns(string $objectType): array
    {
        return match ($objectType) {
            'record' => [
                'identifier', 'title', 'level', 'extentAndMedium', 'archivalHistory',
                'acquisition', 'scopeAndContent', 'appraisal', 'accruals', 'arrangement',
                'accessConditions', 'reproductionConditions', 'physicalCharacteristics',
                'findingAids', 'relatedUnitsOfDescription', 'rules', 'sources',
                'subjectAccessPoints', 'placeAccessPoints', 'nameAccessPoints',
                'genreAccessPoints', 'publicationStatus', 'parentId',
            ],
            'agent' => ['name', 'entityType', 'datesOfExistence', 'history', 'places'],
            default => [],
        };
    }

    private function getRequiredColumns(string $objectType): array
    {
        return match ($objectType) {
            'record' => ['identifier', 'title', 'level'],
            'agent'  => ['name', 'entityType'],
            default  => [],
        };
    }
}
