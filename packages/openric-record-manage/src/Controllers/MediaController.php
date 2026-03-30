<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Media controller — transcription (VTT/SRT), metadata extraction, snippets.
 * Adapted from Heratio MediaController (330 lines).
 */
class MediaController extends Controller
{
    public function transcriptionVtt(int $id): Response
    {
        $transcription = DB::table('media_transcriptions')
            ->where('digital_object_id', $id)->first();

        if (!$transcription) {
            abort(404, 'Transcription not found');
        }

        $segments = json_decode($transcription->segments ?? '[]', true) ?: [];
        $vtt = "WEBVTT\n\n";
        foreach ($segments as $i => $seg) {
            $start = $this->formatTimestamp((float) ($seg['start'] ?? 0));
            $end   = $this->formatTimestamp((float) ($seg['end'] ?? 0));
            $text  = trim($seg['text'] ?? '');
            $vtt  .= ($i + 1) . "\n{$start} --> {$end}\n{$text}\n\n";
        }

        return response($vtt, 200)
            ->header('Content-Type', 'text/vtt; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"transcription-{$id}.vtt\"");
    }

    public function transcriptionSrt(int $id): Response
    {
        $transcription = DB::table('media_transcriptions')
            ->where('digital_object_id', $id)->first();

        if (!$transcription) {
            abort(404, 'Transcription not found');
        }

        $segments = json_decode($transcription->segments ?? '[]', true) ?: [];
        $srt = '';
        foreach ($segments as $i => $seg) {
            $start = $this->formatTimestampSrt((float) ($seg['start'] ?? 0));
            $end   = $this->formatTimestampSrt((float) ($seg['end'] ?? 0));
            $text  = trim($seg['text'] ?? '');
            $srt  .= ($i + 1) . "\n{$start} --> {$end}\n{$text}\n\n";
        }

        return response($srt, 200)
            ->header('Content-Type', 'application/x-subrip; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"transcription-{$id}.srt\"");
    }

    public function transcriptionJson(int $id): JsonResponse
    {
        $transcription = DB::table('media_transcriptions')
            ->where('digital_object_id', $id)->first();

        if (!$transcription) {
            return response()->json(['error' => 'No transcription found'], 404);
        }

        $segments = json_decode($transcription->segments ?? '[]', true);

        return response()->json([
            'full_text'     => $transcription->full_text ?? '',
            'language'      => $transcription->language ?? 'en',
            'confidence'    => $transcription->confidence ?? null,
            'segments'      => $segments,
            'segment_count' => $transcription->segment_count ?? count($segments),
            'duration'      => $transcription->duration ?? null,
        ]);
    }

    public function transcriptionDelete(int $id): JsonResponse
    {
        try {
            DB::table('media_transcriptions')->where('digital_object_id', $id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function snippetStore(Request $request): JsonResponse
    {
        $request->validate([
            'digital_object_id' => 'required|integer',
            'title'             => 'required|string|max:255',
            'start_time'        => 'required|numeric',
            'end_time'          => 'required|numeric',
        ]);

        $id = DB::table('media_snippets')->insertGetId([
            'digital_object_id' => $request->input('digital_object_id'),
            'title'             => $request->input('title'),
            'start_time'        => $request->input('start_time'),
            'end_time'          => $request->input('end_time'),
            'notes'             => $request->input('notes', ''),
            'created_by'        => auth()->id(),
            'created_at'        => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function snippetsList(int $id): JsonResponse
    {
        try {
            $snippets = DB::table('media_snippets')
                ->where('digital_object_id', $id)
                ->orderBy('start_time')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

            return response()->json(['snippets' => $snippets]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function snippetDelete(int $id): JsonResponse
    {
        try {
            DB::table('media_snippets')->where('id', $id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportSnippet(Request $request): JsonResponse
    {
        $snippet = DB::table('media_snippets')->where('id', (int) $request->query('id'))->first();

        if (!$snippet) {
            return response()->json(['error' => 'Snippet not found'], 404);
        }

        return response()->json([
            'id' => $snippet->id, 'digital_object_id' => $snippet->digital_object_id,
            'title' => $snippet->title, 'start_time' => $snippet->start_time,
            'end_time' => $snippet->end_time, 'notes' => $snippet->notes ?? '',
            'duration' => round($snippet->end_time - $snippet->start_time, 3),
        ]);
    }

    private function formatTimestamp(float $seconds): string
    {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds - ($h * 3600) - ($m * 60);
        return sprintf('%02d:%02d:%06.3f', $h, $m, $s);
    }

    private function formatTimestampSrt(float $seconds): string
    {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds - ($h * 3600) - ($m * 60);
        return sprintf('%02d:%02d:%02d,%03d', $h, $m, (int) floor($s), (int) (($s - floor($s)) * 1000));
    }
}
