<?php

declare(strict_types=1);

namespace OpenRiC\Translation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Translation\Contracts\TranslationServiceInterface;

/**
 * Translation controller — adapted from Heratio ahg-translation TranslationController (652 lines).
 *
 * Settings page: configure MT endpoint, API key, timeout.
 * Translate form: select entity + fields, preview translations via AJAX, review & approve.
 * Apply draft: apply/edit a translation draft to the entity.
 * Health check: verify MT endpoint connectivity.
 * Language management: list/enable languages.
 *
 * OpenRiC differences from Heratio:
 * - Uses entity_iri instead of AtoM slug/object_id
 * - Service-layer delegation instead of inline DB queries
 * - PostgreSQL; no MySQL ON DUPLICATE KEY
 * - Additional languages (zh, it) for 19 total
 * - Draft statuses: draft, applied, rejected, superseded
 */
class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationServiceInterface $service,
    ) {
    }

    // =========================================================================
    // Settings
    // =========================================================================

    /**
     * GET|POST /admin/translation/settings
     *
     * Display and save MT configuration.
     * Adapted from Heratio TranslationController::settings().
     */
    public function settings(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('POST')) {
            $endpoint = trim((string) $request->input('endpoint'));
            $timeout  = trim((string) $request->input('timeout'));
            $apiKey   = trim((string) $request->input('api_key'));

            if ($endpoint !== '') {
                $this->service->setSetting('mt.endpoint', $endpoint);
            }
            if ($timeout !== '' && ctype_digit($timeout)) {
                $this->service->setSetting('mt.timeout_seconds', $timeout);
            }
            if ($apiKey !== '') {
                $this->service->setSetting('mt.api_key', $apiKey);
            }

            return redirect()->route('openric.translation.settings')
                ->with('notice', 'Settings updated.');
        }

        $endpoint = (string) $this->service->getSetting('mt.endpoint', 'http://192.168.0.112:5004/ai/v1/translate');
        $timeout  = (string) $this->service->getSetting('mt.timeout_seconds', '60');
        $apiKey   = (string) $this->service->getSetting('mt.api_key', '');

        return view('openric-translation::settings', [
            'endpoint' => $endpoint,
            'timeout'  => $timeout,
            'apiKey'   => $apiKey,
        ]);
    }

    // =========================================================================
    // Translate Form
    // =========================================================================

    /**
     * GET /admin/translation/translate/{entityIri}
     *
     * Show translation form for a RiC entity.
     * Adapted from Heratio TranslationController::translate() which used slug lookups
     * against information_object_i18n. OpenRiC passes the entity IRI directly.
     */
    public function translate(Request $request, string $entityIri): View
    {
        $entityIri = urldecode($entityIri);

        // Retrieve entity title from the triplestore metadata cache or entity table
        $entityTitle = $this->resolveEntityTitle($entityIri);

        // Load user preferences from settings
        $selectedFieldsJson = (string) $this->service->getSetting('translation_fields', '["title","scope_and_content"]');
        $selectedFields     = json_decode($selectedFieldsJson, true) ?: ['title', 'scope_and_content'];
        $defaultTarget      = (string) $this->service->getSetting('translation_target_lang', 'af');
        $saveCultureDefault = $this->service->getSetting('translation_save_culture', '1') === '1';
        $overwriteDefault   = $this->service->getSetting('translation_overwrite', '0') === '1';
        $defaultSource      = (string) $this->service->getSetting('translation_source_lang', 'en');

        // Get cultures that have existing drafts for this entity
        $existingDrafts    = $this->service->getDraftsForEntity($entityIri);
        $availableCultures = array_values(array_unique(array_merge(
            [$defaultSource],
            array_column($existingDrafts, 'source_culture'),
            array_column($existingDrafts, 'target_culture'),
        )));

        $targetLanguages   = $this->service->getTargetLanguages();
        $allFields         = $this->service->getTranslatableFields();

        return view('openric-translation::translate', [
            'entityIri'          => $entityIri,
            'title'              => $entityTitle,
            'culture'            => $defaultSource,
            'availableCultures'  => $availableCultures,
            'targetLanguages'    => $targetLanguages,
            'allFields'          => $allFields,
            'selectedFields'     => $selectedFields,
            'defaultTarget'      => $defaultTarget,
            'saveCultureDefault' => $saveCultureDefault,
            'overwriteDefault'   => $overwriteDefault,
        ]);
    }

    /**
     * POST /admin/translation/translate/{entityIri}
     *
     * AJAX endpoint: translate a single field for an entity via MT.
     * Returns JSON with draft_id, translation, source_text.
     *
     * Adapted from Heratio TranslationController::store() (lines 187-272).
     * Key differences: uses entity_iri instead of slug, service delegation,
     * reads source text from request (OpenRiC doesn't have information_object_i18n).
     */
    public function store(Request $request, string $entityIri): JsonResponse
    {
        $entityIri = urldecode($entityIri);

        $fieldKey       = (string) $request->input('field');
        $targetFieldKey = (string) $request->input('targetField', $fieldKey);
        $source         = (string) $request->input('source', 'en');
        $target         = (string) $request->input('target', 'af');
        $sourceText     = (string) $request->input('sourceText', '');
        $readCulture    = (string) $request->input('readCulture', '');
        $apply          = (int) $request->input('apply', 0) === 1;
        $overwrite      = (int) $request->input('overwrite', 0) === 1;
        $saveCulture    = (int) $request->input('saveCulture', 1) === 1;

        // Validate field keys
        $translatableFields = $this->service->getTranslatableFields();
        if (!isset($translatableFields[$fieldKey])) {
            return response()->json(['ok' => false, 'error' => 'Unsupported source field: ' . $fieldKey]);
        }
        if (!isset($translatableFields[$targetFieldKey])) {
            return response()->json(['ok' => false, 'error' => 'Unsupported target field: ' . $targetFieldKey]);
        }

        // Source text validation
        if (trim($sourceText) === '') {
            return response()->json(['ok' => false, 'error' => 'No source text provided for translation']);
        }

        // Max length for target field
        $maxLength = $this->service->getFieldMaxLength($targetFieldKey);

        // Call the MT service
        $t0     = microtime(true);
        $result = $this->service->translateText($sourceText, $source, $target, $maxLength);
        $elapsedMs = (int) round((microtime(true) - $t0) * 1000);

        // Log the attempt
        $this->service->logAttempt(
            $entityIri,
            $source,
            $target,
            1,
            !empty($result['ok']) ? 'success' : 'error',
            $result['error'] ?? null,
            $elapsedMs,
            Auth::id(),
        );

        if (empty($result['ok'])) {
            return response()->json([
                'ok'          => false,
                'error'       => $result['error'] ?? 'Translation failed',
                'http_status' => $result['http_status'] ?? null,
            ]);
        }

        $translated = (string) $result['translation'];
        $userId     = Auth::id();

        // Create draft
        $draft = $this->service->createDraft(
            $entityIri,
            $targetFieldKey,
            $source,
            $target,
            $sourceText,
            $translated,
            $userId,
        );

        if (empty($draft['ok'])) {
            return response()->json(['ok' => false, 'error' => $draft['error'] ?? 'Failed to create draft']);
        }

        $resp = [
            'ok'           => true,
            'draft_id'     => $draft['draft_id'],
            'deduped'      => $draft['deduped'] ?? false,
            'translation'  => $translated,
            'source_text'  => $sourceText,
            'source_field' => $fieldKey,
            'target_field' => $targetFieldKey,
            'model'        => $result['model'] ?? 'nllb-200',
            'elapsed_ms'   => $elapsedMs,
        ];

        // Auto-apply if requested (Heratio pattern: apply=1 in the same request)
        if ($apply) {
            $targetCulture = $saveCulture ? $target : $source;
            $applied = $this->service->applyDraft((int) $draft['draft_id'], $overwrite, $targetCulture);
            $resp['apply_ok']      = !empty($applied['ok']);
            $resp['saved_culture'] = $targetCulture;
            if (empty($applied['ok'])) {
                $resp['apply_error'] = $applied['error'] ?? 'Apply failed';
            }
        }

        return response()->json($resp);
    }

    // =========================================================================
    // Apply Draft
    // =========================================================================

    /**
     * POST /admin/translation/apply
     *
     * Apply a translation draft (with optional edited text).
     * Adapted from Heratio TranslationController::apply() (lines 279-301).
     */
    public function apply(Request $request): JsonResponse
    {
        $draftId       = (int) $request->input('draftId');
        $overwrite     = (int) $request->input('overwrite', 0) === 1;
        $saveCulture   = (int) $request->input('saveCulture', 1) === 1;
        $targetCulture = (string) $request->input('targetCulture', '');
        $editedText    = $request->input('editedText');

        // If the user edited the text in the review step, update the draft first
        if ($editedText !== null && $editedText !== '') {
            $draft = $this->service->getDraft($draftId);
            if ($draft && $draft['status'] === 'draft') {
                \Illuminate\Support\Facades\DB::table('translation_drafts')
                    ->where('id', $draftId)
                    ->where('status', 'draft')
                    ->update([
                        'translated_text' => $editedText,
                        'updated_at'      => now(),
                    ]);
            }
        }

        if ($saveCulture && $targetCulture !== '') {
            $result = $this->service->applyDraft($draftId, $overwrite, $targetCulture);
        } else {
            $result = $this->service->applyDraft($draftId, $overwrite);
        }

        return response()->json($result);
    }

    /**
     * POST /admin/translation/reject
     *
     * Reject a translation draft.
     */
    public function reject(Request $request): JsonResponse
    {
        $draftId = (int) $request->input('draftId');

        $ok = $this->service->rejectDraft($draftId, Auth::id());

        return response()->json([
            'ok'    => $ok,
            'error' => $ok ? null : 'Draft not found or not in draft state',
        ]);
    }

    // =========================================================================
    // Health Check
    // =========================================================================

    /**
     * GET /admin/translation/health
     *
     * Health check for MT endpoint.
     * Adapted from Heratio TranslationController::health() (lines 308-338).
     */
    public function health(): JsonResponse
    {
        $result = $this->service->healthCheck();

        return response()->json($result);
    }

    // =========================================================================
    // Language Management
    // =========================================================================

    /**
     * GET /admin/translation/languages
     *
     * List available languages from settings.
     * Adapted from Heratio TranslationController::languages() (lines 345-373).
     */
    public function languages(): View
    {
        $langData = $this->service->getEnabledLanguages();

        return view('openric-translation::languages', [
            'languages'       => $langData['languages'],
            'enabledCultures' => $langData['enabled_cultures'],
            'defaultCulture'  => $langData['default_culture'],
        ]);
    }

    /**
     * POST /admin/translation/languages
     *
     * Add/enable a language in the system.
     * Adapted from Heratio TranslationController::addLanguage() (lines 380-409).
     */
    public function addLanguage(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:8',
        ]);

        $code = trim((string) $request->input('code'));

        // Validate against known languages
        $targetLanguages = $this->service->getTargetLanguages();
        if (!isset($targetLanguages[$code])) {
            return redirect()->route('openric.translation.languages')
                ->with('error', 'Unknown language code: ' . $code);
        }

        $this->service->enableLanguage($code);

        return redirect()->route('openric.translation.languages')
            ->with('notice', 'Language "' . $code . ' — ' . $targetLanguages[$code] . '" enabled.');
    }

    // =========================================================================
    // Drafts API
    // =========================================================================

    /**
     * GET /admin/translation/drafts/{entityIri}
     *
     * List translation drafts for an entity (JSON).
     * New in OpenRiC — Heratio had no draft listing endpoint.
     */
    public function drafts(Request $request, string $entityIri): JsonResponse
    {
        $entityIri = urldecode($entityIri);
        $status    = $request->input('status');

        $drafts = $this->service->getDraftsForEntity(
            $entityIri,
            is_string($status) ? $status : null,
        );

        return response()->json(['ok' => true, 'drafts' => $drafts, 'total' => count($drafts)]);
    }

    /**
     * GET /admin/translation/log/{entityIri}
     *
     * Translation log for an entity (JSON).
     * New in OpenRiC — Heratio logged but had no read endpoint.
     */
    public function log(Request $request, string $entityIri): JsonResponse
    {
        $entityIri = urldecode($entityIri);
        $limit     = min(200, max(1, (int) $request->input('limit', 50)));

        $entries = $this->service->getLogForEntity($entityIri, $limit);

        return response()->json(['ok' => true, 'log' => $entries, 'total' => count($entries)]);
    }

    // =========================================================================
    // Settings Persistence (field defaults)
    // =========================================================================

    /**
     * POST /admin/translation/settings/fields
     *
     * Save default selected fields and language preferences.
     * New in OpenRiC — Heratio stored these in the same settings page.
     */
    public function saveFieldDefaults(Request $request): JsonResponse
    {
        $fields = $request->input('fields');
        if (is_array($fields)) {
            // Validate each field key
            $translatableFields = $this->service->getTranslatableFields();
            $validFields = array_values(array_intersect($fields, array_keys($translatableFields)));
            $this->service->setSetting('translation_fields', json_encode($validFields, JSON_THROW_ON_ERROR));
        }

        $targetLang = $request->input('target_lang');
        if (is_string($targetLang) && $targetLang !== '') {
            $this->service->setSetting('translation_target_lang', $targetLang);
        }

        $sourceLang = $request->input('source_lang');
        if (is_string($sourceLang) && $sourceLang !== '') {
            $this->service->setSetting('translation_source_lang', $sourceLang);
        }

        $saveCulture = $request->input('save_culture');
        if ($saveCulture !== null) {
            $this->service->setSetting('translation_save_culture', $saveCulture ? '1' : '0');
        }

        $overwrite = $request->input('overwrite');
        if ($overwrite !== null) {
            $this->service->setSetting('translation_overwrite', $overwrite ? '1' : '0');
        }

        return response()->json(['ok' => true]);
    }

    // =========================================================================
    // Batch Translation
    // =========================================================================

    /**
     * POST /admin/translation/batch
     *
     * Translate multiple fields for an entity in one request.
     * New in OpenRiC — Heratio did sequential AJAX calls from the frontend.
     * This reduces HTTP overhead for multi-field translations.
     */
    public function batch(Request $request): JsonResponse
    {
        $entityIri = (string) $request->input('entity_iri');
        $source    = (string) $request->input('source', 'en');
        $target    = (string) $request->input('target', 'af');
        $fields    = $request->input('fields', []);

        if (!is_array($fields) || empty($fields)) {
            return response()->json(['ok' => false, 'error' => 'No fields provided']);
        }
        if ($source === $target) {
            return response()->json(['ok' => false, 'error' => 'Source and target language must differ']);
        }

        $translatableFields = $this->service->getTranslatableFields();
        $results            = [];
        $okCount            = 0;
        $failCount          = 0;
        $totalElapsed       = 0;
        $t0                 = microtime(true);

        foreach ($fields as $fieldData) {
            $fieldKey   = (string) ($fieldData['field'] ?? '');
            $sourceText = (string) ($fieldData['source_text'] ?? '');

            if (!isset($translatableFields[$fieldKey])) {
                $results[] = ['field' => $fieldKey, 'ok' => false, 'error' => 'Unsupported field'];
                $failCount++;
                continue;
            }
            if (trim($sourceText) === '') {
                $results[] = ['field' => $fieldKey, 'ok' => false, 'error' => 'Empty source text'];
                $failCount++;
                continue;
            }

            $maxLength = $this->service->getFieldMaxLength($fieldKey);
            $result    = $this->service->translateText($sourceText, $source, $target, $maxLength);

            if (empty($result['ok'])) {
                $results[] = [
                    'field'       => $fieldKey,
                    'ok'          => false,
                    'error'       => $result['error'] ?? 'Translation failed',
                    'http_status' => $result['http_status'] ?? null,
                ];
                $failCount++;
                continue;
            }

            $translated = (string) $result['translation'];

            // Create draft
            $draft = $this->service->createDraft(
                $entityIri,
                $fieldKey,
                $source,
                $target,
                $sourceText,
                $translated,
                Auth::id(),
            );

            if (empty($draft['ok'])) {
                $results[] = ['field' => $fieldKey, 'ok' => false, 'error' => $draft['error'] ?? 'Draft creation failed'];
                $failCount++;
                continue;
            }

            $results[] = [
                'field'       => $fieldKey,
                'ok'          => true,
                'draft_id'    => $draft['draft_id'],
                'deduped'     => $draft['deduped'] ?? false,
                'translation' => $translated,
                'source_text' => $sourceText,
                'model'       => $result['model'] ?? 'nllb-200',
                'elapsed_ms'  => $result['elapsed_ms'] ?? 0,
            ];
            $okCount++;
            $totalElapsed += ($result['elapsed_ms'] ?? 0);
        }

        $batchElapsed = (int) round((microtime(true) - $t0) * 1000);

        // Log batch attempt
        $this->service->logAttempt(
            $entityIri,
            $source,
            $target,
            count($fields),
            $failCount === 0 ? 'success' : ($okCount === 0 ? 'error' : 'partial'),
            $failCount > 0 ? ($failCount . ' of ' . count($fields) . ' fields failed') : null,
            $batchElapsed,
            Auth::id(),
        );

        return response()->json([
            'ok'          => $okCount > 0,
            'results'     => $results,
            'ok_count'    => $okCount,
            'fail_count'  => $failCount,
            'total_ms'    => $batchElapsed,
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Resolve entity title from the entity IRI.
     *
     * Checks the entity metadata cache table, falls back to extracting
     * the local name from the IRI, or returns 'Untitled'.
     */
    private function resolveEntityTitle(string $entityIri): string
    {
        // Try entity metadata cache (if available)
        try {
            $title = \Illuminate\Support\Facades\DB::table('entity_metadata')
                ->where('entity_iri', $entityIri)
                ->value('title');

            if (is_string($title) && trim($title) !== '') {
                return $title;
            }
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        // Try RiC record descriptions
        try {
            $title = \Illuminate\Support\Facades\DB::table('record_descriptions')
                ->where('entity_iri', $entityIri)
                ->value('title');

            if (is_string($title) && trim($title) !== '') {
                return $title;
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Extract local name from IRI as fallback
        $parts = explode('/', $entityIri);
        $local = end($parts);
        if ($local !== false && $local !== '') {
            // Also try after # fragment
            $fragParts = explode('#', $local);
            return end($fragParts) ?: 'Untitled';
        }

        return 'Untitled';
    }
}
