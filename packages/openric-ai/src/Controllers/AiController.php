<?php

declare(strict_types=1);

namespace OpenRiC\AI\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\AI\Services\HtrService;
use OpenRiC\AI\Services\LlmService;
use OpenRiC\AI\Services\NerService;
use OpenRiC\AI\Services\OllamaEmbeddingService;

/**
 * AI Controller — adapted from Heratio AiController (4,556 lines).
 *
 * Key actions from Heratio mapped:
 *   1.  dashboard         — AI services overview + stats + health
 *   2.  config            — LLM configuration CRUD (GET + POST)
 *   3.  configStore       — Create/update LLM config (POST)
 *   4.  configDelete      — Delete LLM config (DELETE)
 *   5.  summarize         — Summarize text (JSON)
 *   6.  translate         — Translate text (JSON)
 *   7.  extractEntities   — NER extraction (JSON)
 *   8.  suggestDescription — Generate description (JSON)
 *   9.  spellcheck        — Spell/grammar check (JSON)
 *  10.  testConnection    — Test LLM connection (JSON)
 *  11.  nerExtract        — Extract entities for a record (POST)
 *  12.  nerEntities       — Get entities for a record (JSON)
 *  13.  nerUpdate         — Update entity status (POST)
 *  14.  nerBulkSave       — Bulk update entities (POST)
 *  15.  nerReview         — NER review page
 *  16.  htrHealth         — HTR service health (JSON)
 *  17.  suggestions       — Browse AI suggestions
 *  18.  suggestionDecision — Accept/reject suggestion (POST)
 *  19.  jobs              — Browse AI batch jobs
 *  20.  health            — All provider health (JSON)
 */
class AiController extends Controller
{
    public function __construct(
        private readonly LlmService $llm,
        private readonly NerService $ner,
        private readonly HtrService $htr,
        private readonly OllamaEmbeddingService $embedding,
    ) {}

    // =========================================================================
    // Dashboard & Configuration
    // =========================================================================

    /**
     * #1 — AI services dashboard.
     */
    public function dashboard(): View
    {
        $stats = $this->llm->getUsageStats();
        $health = $this->llm->getAllHealth();
        $nerStats = $this->ner->getStats();
        $htrHealth = $this->htr->health();

        return view('openric-ai::dashboard', compact('stats', 'health', 'nerStats', 'htrHealth'));
    }

    /**
     * #2 — LLM configuration page.
     */
    public function config(): View
    {
        $configs = $this->llm->getConfigurations();
        $settings = $this->llm->getAiSettingsByFeature('general');

        return view('openric-ai::config', compact('configs', 'settings'));
    }

    /**
     * #3 — Create or update LLM config (POST).
     */
    public function configStore(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'provider' => 'required|string|in:ollama,openai,anthropic',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'endpoint_url' => 'nullable|url|max:2048',
            'api_key' => 'nullable|string|max:500',
            'max_tokens' => 'integer|min:1|max:100000',
            'temperature' => 'numeric|min:0|max:2',
            'timeout_seconds' => 'integer|min:5|max:600',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($data['id'])) {
            $this->llm->updateConfiguration((int) $data['id'], $data);
            $message = 'Configuration updated.';
        } else {
            $this->llm->createConfiguration($data);
            $message = 'Configuration created.';
        }

        return redirect()->route('admin.ai.config')->with('success', $message);
    }

    /**
     * #4 — Delete LLM config.
     */
    public function configDelete(int $id)
    {
        $this->llm->deleteConfiguration($id);
        return redirect()->route('admin.ai.config')->with('success', 'Configuration deleted.');
    }

    // =========================================================================
    // AJAX JSON Endpoints — from Heratio lines 200-400
    // =========================================================================

    /**
     * #5 — Summarize text.
     */
    public function summarize(Request $request): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:50000', 'max_length' => 'integer|min:50|max:1000']);

        $start = microtime(true);
        $result = $this->llm->summarize($request->input('text'), (int) $request->input('max_length', 200));
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        return response()->json([
            'success' => $result !== null,
            'summary' => $result,
            'generation_time_ms' => $elapsed,
        ]);
    }

    /**
     * #6 — Translate text.
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:50000', 'target_language' => 'required|string|max:50']);

        $start = microtime(true);
        $result = $this->llm->translate($request->input('text'), $request->input('target_language'));
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        return response()->json([
            'success' => $result !== null,
            'translation' => $result,
            'target_language' => $request->input('target_language'),
            'generation_time_ms' => $elapsed,
        ]);
    }

    /**
     * #7 — Extract named entities.
     */
    public function extractEntities(Request $request): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:50000']);

        $start = microtime(true);
        $entities = $this->ner->extract($request->input('text'));
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        $total = array_sum(array_map('count', $entities));

        return response()->json([
            'success' => $total > 0,
            'entities' => $entities,
            'total' => $total,
            'generation_time_ms' => $elapsed,
        ]);
    }

    /**
     * #8 — Suggest archival description.
     */
    public function suggestDescription(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:1000', 'context' => 'nullable|string|max:10000']);

        $start = microtime(true);
        $result = $this->llm->suggestDescription($request->input('title'), $request->input('context', ''));
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        // Store suggestion if entity_iri provided
        if ($result !== null && $request->has('entity_iri')) {
            \Illuminate\Support\Facades\DB::table('ai_suggestions')->insert([
                'entity_iri' => $request->input('entity_iri'),
                'field_name' => 'rico:scopeAndContent',
                'suggested_text' => $result,
                'suggestion_type' => 'description',
                'model_used' => $this->llm->getDefaultConfig()->model ?? 'unknown',
                'generation_time_ms' => $elapsed,
                'status' => 'pending',
                'generated_for' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => $result !== null,
            'suggestion' => $result,
            'generation_time_ms' => $elapsed,
        ]);
    }

    /**
     * #9 — Spellcheck text.
     */
    public function spellcheck(Request $request): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:50000']);

        $start = microtime(true);
        $result = $this->llm->spellcheck($request->input('text'));
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        return response()->json(array_merge($result, [
            'success' => true,
            'generation_time_ms' => $elapsed,
        ]));
    }

    /**
     * #10 — Test LLM connection.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $configId = $request->input('config_id') ? (int) $request->input('config_id') : null;
        $result = $this->llm->testConnection($configId);

        return response()->json($result);
    }

    // =========================================================================
    // NER Entity Management — from Heratio lines 400-800
    // =========================================================================

    /**
     * #11 — Extract entities for a record.
     */
    public function nerExtract(Request $request): JsonResponse
    {
        $request->validate(['entity_iri' => 'required|string|max:2048', 'text' => 'required|string|max:100000']);

        $count = $this->ner->extractAndStore(
            $request->input('entity_iri'),
            $request->input('text'),
            auth()->id()
        );

        return response()->json(['success' => true, 'entities_found' => $count]);
    }

    /**
     * #12 — Get entities for a record.
     */
    public function nerEntities(Request $request): JsonResponse
    {
        $iri = $request->input('entity_iri', '');
        $entities = $this->ner->getEntitiesForRecord($iri);

        return response()->json(['entities' => $entities]);
    }

    /**
     * #13 — Update entity status.
     */
    public function nerUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'entity_id' => 'required|integer',
            'action' => 'required|string|in:link,approve,reject',
            'linked_iri' => 'nullable|string|max:2048',
        ]);

        $success = $this->ner->updateEntityStatus(
            (int) $request->input('entity_id'),
            $request->input('action'),
            $request->input('linked_iri'),
            auth()->id()
        );

        return response()->json(['success' => $success]);
    }

    /**
     * #14 — Bulk update entities.
     */
    public function nerBulkSave(Request $request): JsonResponse
    {
        $request->validate(['decisions' => 'required|array']);

        $count = $this->ner->bulkUpdateEntities($request->input('decisions'), auth()->id());

        return response()->json(['success' => true, 'updated' => $count]);
    }

    /**
     * #15 — NER review page.
     */
    public function nerReview(Request $request): View
    {
        $pendingRecords = $this->ner->getPendingRecords();
        $stats = $this->ner->getStats();

        return view('openric-ai::ner-review', compact('pendingRecords', 'stats'));
    }

    // =========================================================================
    // HTR — from Heratio lines 800-1200
    // =========================================================================

    /**
     * #16 — HTR service health.
     */
    public function htrHealth(): JsonResponse
    {
        return response()->json($this->htr->health());
    }

    // =========================================================================
    // Suggestions & Jobs — from Heratio lines 1200-1500
    // =========================================================================

    /**
     * #17 — Browse AI suggestions.
     */
    public function suggestions(Request $request): View
    {
        $status = $request->input('status', 'pending');
        $page = max(1, (int) $request->input('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $query = \Illuminate\Support\Facades\DB::table('ai_suggestions');
        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $items = (clone $query)->orderByDesc('created_at')->offset($offset)->limit($limit)->get();
        $totalPages = max(1, (int) ceil($total / $limit));

        return view('openric-ai::suggestions', compact('items', 'total', 'page', 'totalPages', 'status'));
    }

    /**
     * #18 — Accept/reject suggestion.
     */
    public function suggestionDecision(Request $request, int $id)
    {
        $request->validate([
            'decision' => 'required|string|in:accepted,rejected,edited',
            'applied_text' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::table('ai_suggestions')->where('id', $id)->update([
            'status' => $request->input('decision'),
            'applied_text' => $request->input('applied_text'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.ai.suggestions')->with('success', 'Suggestion ' . $request->input('decision') . '.');
    }

    /**
     * #19 — Browse AI batch jobs.
     */
    public function jobs(Request $request): View
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $total = \Illuminate\Support\Facades\DB::table('ai_jobs')->count();
        $items = \Illuminate\Support\Facades\DB::table('ai_jobs')
            ->orderByDesc('created_at')
            ->offset($offset)->limit($limit)->get();
        $totalPages = max(1, (int) ceil($total / $limit));

        return view('openric-ai::jobs', compact('items', 'total', 'page', 'totalPages'));
    }

    /**
     * #20 — All provider health check.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'llm' => $this->llm->getAllHealth(),
            'ner' => $this->ner->getApiHealth(),
            'htr' => $this->htr->health(),
        ]);
    }
}
