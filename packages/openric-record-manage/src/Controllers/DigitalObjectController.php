<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Digital object controller — upload, delete, show metadata.
 * Adapted from Heratio DigitalObjectController (172 lines).
 */
class DigitalObjectController extends Controller
{
    /**
     * Handle file upload for a record.
     */
    public function upload(Request $request, int $recordId): RedirectResponse
    {
        $request->validate([
            'digital_object' => 'required|file|max:102400',
        ]);

        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $existing = DB::table('digital_objects')
            ->where('record_id', $recordId)
            ->where('usage', 'master')
            ->first();

        if ($existing) {
            return redirect()->route('records.edit', ['iri' => urlencode($record->iri)])
                ->with('error', 'A digital object already exists. Delete the current one before uploading a new file.');
        }

        try {
            $file = $request->file('digital_object');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('digital-objects/' . $recordId, $filename, 'public');

            DB::table('digital_objects')->insert([
                'record_id'  => $recordId,
                'usage'      => 'master',
                'name'       => $file->getClientOriginalName(),
                'path'       => $path,
                'mime_type'  => $file->getMimeType(),
                'byte_size'  => $file->getSize(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('records.edit', ['iri' => urlencode($record->iri)])
                ->with('success', 'Digital object uploaded successfully.');
        } catch (\Exception $e) {
            return redirect()->route('records.edit', ['iri' => urlencode($record->iri)])
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a digital object and its derivatives.
     */
    public function delete(Request $request, int $id): RedirectResponse
    {
        $doRow = DB::table('digital_objects')->where('id', $id)->first();
        if (!$doRow) {
            abort(404);
        }

        $record = DB::table('records')->where('id', $doRow->record_id)->first();
        if (!$record) {
            abort(404);
        }

        DB::table('digital_objects')
            ->where('id', $id)
            ->orWhere('parent_id', $id)
            ->delete();

        return redirect()->route('records.edit', ['iri' => urlencode($record->iri)])
            ->with('success', 'Digital object deleted successfully.');
    }

    /**
     * Display digital object metadata page.
     */
    public function show(int $id): View
    {
        $doRow = DB::table('digital_objects')->where('id', $id)->first();
        if (!$doRow) {
            abort(404);
        }

        $record = DB::table('records')->where('id', $doRow->record_id)->first();

        return view('record-manage::digitalobject.show', [
            'digitalObject' => $doRow,
            'record'        => $record,
        ]);
    }

    /**
     * Edit digital object metadata.
     */
    public function edit(int $id): View
    {
        $doRow = DB::table('digital_objects')->where('id', $id)->first();
        if (!$doRow) {
            abort(404);
        }

        $record = DB::table('records')->where('id', $doRow->record_id)->first();

        return view('record-manage::digitalobject.edit', [
            'digitalObject' => $doRow,
            'record'        => $record,
        ]);
    }

    /**
     * Show rights for a digital object.
     */
    public function rights(int $id): View
    {
        $doRow = DB::table('digital_objects')->where('id', $id)->first();
        if (!$doRow) {
            abort(404);
        }

        $record = DB::table('records')->where('id', $doRow->record_id)->first();

        return view('record-manage::digitalobject.rights', [
            'digitalObject' => $doRow,
            'record'        => $record,
        ]);
    }
}
