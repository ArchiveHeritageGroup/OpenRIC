<?php

declare(strict_types=1);

namespace OpenRiC\Translation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenRiC\Translation\Contracts\TranslationServiceInterface;

/**
 * Translation service — adapted from Heratio ahg-translation TranslationController (652 lines).
 *
 * Integrates with NLLB-200 machine translation endpoint for translating RiC entity
 * descriptive fields across 19 languages (11 South African official + 8 international).
 * Manages drafts with deduplication, apply/reject workflow, full audit logging,
 * configurable MT settings (endpoint, API key, timeout), and field-level max lengths.
 *
 * OpenRiC differences from Heratio:
 * - Uses entity_iri (RiC IRI) instead of AtoM object_id / slug lookup
 * - PostgreSQL ON CONFLICT for upserts instead of MySQL ON DUPLICATE KEY
 * - Adds zh (Chinese) and it (Italian) to the language list (19 total)
 * - Service/Controller split (Heratio had everything in one controller)
 * - Proper interface-driven DI
 */
class TranslationService implements TranslationServiceInterface
{
    // ── Constants ────────────────────────────────────────────────────────

    /**
     * 21 translatable fields from Heratio's ALLOWED_FIELDS.
     * Maps UI field keys to descriptive labels.
     */
    private const TRANSLATABLE_FIELDS = [
        'title'                              => 'Title',
        'alternate_title'                    => 'Alternate Title',
        'edition'                            => 'Edition',
        'extent_and_medium'                  => 'Extent and Medium',
        'archival_history'                   => 'Archival History',
        'acquisition'                        => 'Acquisition',
        'scope_and_content'                  => 'Scope and Content',
        'appraisal'                          => 'Appraisal',
        'accruals'                           => 'Accruals',
        'arrangement'                        => 'Arrangement',
        'access_conditions'                  => 'Access Conditions',
        'reproduction_conditions'            => 'Reproduction Conditions',
        'physical_characteristics'           => 'Physical Characteristics',
        'finding_aids'                       => 'Finding Aids',
        'location_of_originals'              => 'Location of Originals',
        'location_of_copies'                 => 'Location of Copies',
        'related_units_of_description'       => 'Related Units of Description',
        'institution_responsible_identifier' => 'Institution Responsible Identifier',
        'rules'                              => 'Rules',
        'sources'                            => 'Sources',
        'revision_history'                   => 'Revision History',
    ];

    /**
     * Max lengths for fields with constrained columns.
     * Fields not listed default to 65535.
     */
    private const FIELD_MAX_LENGTHS = [
        'title'                              => 1024,
        'alternate_title'                    => 1024,
        'edition'                            => 255,
        'institution_responsible_identifier' => 1024,
    ];

    /**
     * 19 target languages: 11 SA official + 8 international.
     */
    private const TARGET_LANGUAGES = [
        'en'  => 'English',
        'af'  => 'Afrikaans',
        'zu'  => 'isiZulu',
        'xh'  => 'isiXhosa',
        'st'  => 'Sesotho',
        'tn'  => 'Setswana',
        'ss'  => 'SiSwati',
        'ts'  => 'Xitsonga',
        've'  => 'Tshivenda',
        'nr'  => 'isiNdebele',
        'nso' => 'Sepedi (Northern Sotho)',
        'fr'  => 'French',
        'de'  => 'German',
        'pt'  => 'Portuguese',
        'es'  => 'Spanish',
        'nl'  => 'Dutch',
        'it'  => 'Italian',
        'ar'  => 'Arabic',
        'zh'  => 'Chinese',
    ];

    /**
     * Default MT endpoint (NLLB-200 service).
     */
    private const DEFAULT_ENDPOINT = 'http://192.168.0.112:5004/ai/v1/translate';

    /**
     * Default timeout in seconds for MT requests.
     */
    private const DEFAULT_TIMEOUT = 60;

    // ── MT Core ─────────────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     *
     * Calls the NLLB-200 translation API via cURL.
     * Adapted from Heratio TranslationController::translateText().
     */
    public function translateText(string $text, string $sourceCulture, string $targetCulture, ?int $maxLength = null): array
    {
        $endpoint = $this->getSetting('mt.endpoint', self::DEFAULT_ENDPOINT);
        $apiKey   = (string) $this->getSetting('mt.api_key', '');
        $timeout  = (int) $this->getSetting('mt.timeout_seconds', (string) self::DEFAULT_TIMEOUT);

        $payloadData = [
            'text'   => $text,
            'source' => $sourceCulture,
            'target' => $targetCulture,
        ];
        if ($maxLength !== null) {
            $payloadData['max_length'] = $maxLength;
        }
        $payload = json_encode($payloadData, JSON_THROW_ON_ERROR);

        $headers = ['Content-Type: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        $t0 = microtime(true);

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [
                'ok'          => false,
                'translation' => null,
                'http_status' => null,
                'error'       => 'Failed to initialise cURL handle',
                'elapsed_ms'  => 0,
                'endpoint'    => $endpoint,
                'model'       => null,
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        ]);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $elapsedMs = (int) round((microtime(true) - $t0) * 1000);

        // cURL transport error
        if ($errno !== 0) {
            Log::warning('Translation MT cURL error', [
                'endpoint'   => $endpoint,
                'curl_errno' => $errno,
                'curl_error' => $errstr,
                'elapsed_ms' => $elapsedMs,
            ]);

            return [
                'ok'          => false,
                'translation' => null,
                'http_status' => $status ?: null,
                'error'       => 'cURL error ' . $errno . ': ' . $errstr,
                'elapsed_ms'  => $elapsedMs,
                'endpoint'    => $endpoint,
                'model'       => null,
            ];
        }

        // Parse response JSON
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            Log::warning('Translation MT invalid JSON', [
                'endpoint'    => $endpoint,
                'http_status' => $status,
                'raw_length'  => strlen((string) $raw),
            ]);

            return [
                'ok'          => false,
                'translation' => null,
                'http_status' => $status,
                'error'       => 'Invalid JSON from MT endpoint',
                'elapsed_ms'  => $elapsedMs,
                'endpoint'    => $endpoint,
                'model'       => null,
            ];
        }

        // Accept multiple response field names (NLLB-200 variants)
        $translation = $data['translated'] ?? $data['translatedText'] ?? $data['translation'] ?? null;

        if ($status < 200 || $status >= 300 || !is_string($translation)) {
            $errorMsg = $data['detail'] ?? $data['error'] ?? 'MT endpoint returned non-2xx or missing translation';

            Log::warning('Translation MT failure', [
                'endpoint'    => $endpoint,
                'http_status' => $status,
                'error'       => $errorMsg,
                'source'      => $sourceCulture,
                'target'      => $targetCulture,
            ]);

            return [
                'ok'          => false,
                'translation' => null,
                'http_status' => $status,
                'error'       => $errorMsg,
                'elapsed_ms'  => $elapsedMs,
                'endpoint'    => $endpoint,
                'model'       => null,
            ];
        }

        return [
            'ok'          => true,
            'translation' => $translation,
            'http_status' => $status,
            'error'       => null,
            'elapsed_ms'  => $elapsedMs,
            'endpoint'    => $endpoint,
            'model'       => $data['model'] ?? 'nllb-200',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Sends a simple test phrase ("test" in Afrikaans) to the MT endpoint.
     * Adapted from Heratio TranslationController::health().
     */
    public function healthCheck(): array
    {
        $endpoint = (string) $this->getSetting('mt.endpoint', self::DEFAULT_ENDPOINT);
        $apiKey   = (string) $this->getSetting('mt.api_key', '');

        $headers = ['Content-Type: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [
                'ok'          => false,
                'endpoint'    => $endpoint,
                'http_status' => 0,
                'curl_error'  => 'Failed to initialise cURL handle',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode([
                'source' => 'af',
                'target' => 'en',
                'text'   => 'toets',
            ]),
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $err    = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $ok = ($errno === 0) && ($status >= 200 && $status < 500);

        return [
            'ok'          => $ok,
            'endpoint'    => $endpoint,
            'http_status' => $status,
            'curl_error'  => $errno ? ($errno . ': ' . $err) : null,
        ];
    }

    // ── Draft Management ────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     *
     * Creates a translation draft with SHA-256 deduplication on source text.
     * Adapted from Heratio TranslationController::createDraft().
     */
    public function createDraft(
        string $entityIri,
        string $fieldName,
        string $sourceCulture,
        string $targetCulture,
        string $sourceText,
        string $translatedText,
        ?int $userId = null,
    ): array {
        if (!isset(self::TRANSLATABLE_FIELDS[$fieldName])) {
            return ['ok' => false, 'draft_id' => null, 'deduped' => false, 'error' => 'Unsupported field: ' . $fieldName];
        }

        $sourceHash = hash('sha256', $sourceText);

        // Check for existing identical draft (deduplication)
        $existing = DB::table('translation_drafts')
            ->where('entity_iri', $entityIri)
            ->where('field_name', $fieldName)
            ->where('source_culture', $sourceCulture)
            ->where('target_culture', $targetCulture)
            ->where('source_hash', $sourceHash)
            ->where('status', 'draft')
            ->first();

        if ($existing) {
            // Update the translated text if it changed
            DB::table('translation_drafts')
                ->where('id', $existing->id)
                ->update([
                    'translated_text' => $translatedText,
                    'updated_at'      => now(),
                ]);

            return ['ok' => true, 'draft_id' => (int) $existing->id, 'deduped' => true, 'error' => null];
        }

        try {
            $draftId = (int) DB::table('translation_drafts')->insertGetId([
                'entity_iri'      => $entityIri,
                'field_name'      => $fieldName,
                'source_culture'  => $sourceCulture,
                'target_culture'  => $targetCulture,
                'source_hash'     => $sourceHash,
                'source_text'     => $sourceText,
                'translated_text' => $translatedText,
                'status'          => 'draft',
                'user_id'         => $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return ['ok' => true, 'draft_id' => $draftId, 'deduped' => false, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Translation draft creation failed', [
                'entity_iri' => $entityIri,
                'field'      => $fieldName,
                'error'      => $e->getMessage(),
            ]);

            return ['ok' => false, 'draft_id' => null, 'deduped' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * {@inheritDoc}
     *
     * Applies a draft to the entity's descriptive metadata in the triplestore staging table.
     * In OpenRiC, translations are stored in the translation_drafts table with status 'applied'.
     * The actual RDF update is triggered by the RiC sync pipeline.
     *
     * Adapted from Heratio TranslationController::applyDraft() which wrote directly to
     * information_object_i18n. OpenRiC marks the draft as applied; the sync pipeline
     * handles the triplestore write.
     */
    public function applyDraft(int $draftId, bool $overwrite = false, ?string $targetCulture = null): array
    {
        $draft = DB::table('translation_drafts')->where('id', $draftId)->first();
        if (!$draft) {
            return ['ok' => false, 'culture' => null, 'error' => 'Draft not found'];
        }
        if ($draft->status !== 'draft') {
            return ['ok' => false, 'culture' => null, 'error' => 'Draft not in draft state (current: ' . $draft->status . ')'];
        }

        if (!isset(self::TRANSLATABLE_FIELDS[$draft->field_name])) {
            return ['ok' => false, 'culture' => null, 'error' => 'Field not in translatable list: ' . $draft->field_name];
        }

        $culture = $targetCulture ?? $draft->target_culture;

        // Check for existing applied translation for same entity/field/culture (unless overwrite)
        if (!$overwrite) {
            $existingApplied = DB::table('translation_drafts')
                ->where('entity_iri', $draft->entity_iri)
                ->where('field_name', $draft->field_name)
                ->where('target_culture', $culture)
                ->where('status', 'applied')
                ->exists();

            if ($existingApplied) {
                return ['ok' => false, 'culture' => $culture, 'error' => 'Target field already has an applied translation; use overwrite=1 to replace'];
            }
        }

        // If overwriting, reject previous applied drafts for same field/culture
        if ($overwrite) {
            DB::table('translation_drafts')
                ->where('entity_iri', $draft->entity_iri)
                ->where('field_name', $draft->field_name)
                ->where('target_culture', $culture)
                ->where('status', 'applied')
                ->update([
                    'status'     => 'superseded',
                    'updated_at' => now(),
                ]);
        }

        DB::table('translation_drafts')
            ->where('id', $draftId)
            ->update([
                'status'         => 'applied',
                'target_culture' => $culture,
                'applied_at'     => now(),
                'updated_at'     => now(),
            ]);

        return ['ok' => true, 'culture' => $culture, 'error' => null];
    }

    /**
     * {@inheritDoc}
     */
    public function rejectDraft(int $draftId, ?int $userId = null): bool
    {
        $affected = DB::table('translation_drafts')
            ->where('id', $draftId)
            ->where('status', 'draft')
            ->update([
                'status'     => 'rejected',
                'user_id'    => $userId ?? DB::raw('user_id'),
                'updated_at' => now(),
            ]);

        return $affected > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getDraft(int $draftId): ?array
    {
        $row = DB::table('translation_drafts')->where('id', $draftId)->first();

        return $row ? (array) $row : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDraftsForEntity(string $entityIri, ?string $status = null): array
    {
        $query = DB::table('translation_drafts')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get()->map(fn ($row): array => (array) $row)->toArray();
    }

    // ── Logging ─────────────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     *
     * Adapted from Heratio TranslationController::logAttempt().
     * Uses a dedicated translation_log table with structured fields.
     */
    public function logAttempt(
        string $entityIri,
        string $sourceCulture,
        string $targetCulture,
        int $fieldCount,
        string $status,
        ?string $errorMessage,
        int $durationMs,
        ?int $userId = null,
    ): void {
        try {
            DB::table('translation_log')->insert([
                'entity_iri'     => $entityIri,
                'source_culture' => $sourceCulture,
                'target_culture' => $targetCulture,
                'field_count'    => $fieldCount,
                'status'         => $status,
                'error_message'  => $errorMessage,
                'duration_ms'    => $durationMs,
                'user_id'        => $userId,
                'created_at'     => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write translation log', [
                'entity_iri' => $entityIri,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLogForEntity(string $entityIri, int $limit = 50): array
    {
        return DB::table('translation_log')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->toArray();
    }

    // ── Settings ────────────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     *
     * Adapted from Heratio TranslationController::getSetting().
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            $value = DB::table('translation_settings')
                ->where('setting_key', $key)
                ->value('setting_value');

            return $value !== null ? $value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * {@inheritDoc}
     *
     * PostgreSQL ON CONFLICT upsert (Heratio used MySQL ON DUPLICATE KEY).
     */
    public function setSetting(string $key, string $value): void
    {
        try {
            $exists = DB::table('translation_settings')
                ->where('setting_key', $key)
                ->exists();

            if ($exists) {
                DB::table('translation_settings')
                    ->where('setting_key', $key)
                    ->update([
                        'setting_value' => $value,
                        'updated_at'    => now(),
                    ]);
            } else {
                DB::table('translation_settings')->insert([
                    'setting_key'   => $key,
                    'setting_value' => $value,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to save translation setting', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Field Mapping ───────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     */
    public function getTranslatableFields(): array
    {
        return self::TRANSLATABLE_FIELDS;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldMaxLength(string $fieldKey): int
    {
        return self::FIELD_MAX_LENGTHS[$fieldKey] ?? 65535;
    }

    // ── Language Support ────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     */
    public function getTargetLanguages(): array
    {
        return self::TARGET_LANGUAGES;
    }

    /**
     * {@inheritDoc}
     *
     * Adapted from Heratio TranslationController::languages().
     * Reads enabled cultures from translation_settings instead of AtoM's setting table.
     */
    public function getEnabledLanguages(): array
    {
        $enabledJson = $this->getSetting('i18n_languages', null);
        $enabledCultures = $enabledJson !== null ? (json_decode((string) $enabledJson, true) ?: ['en']) : ['en'];
        $defaultCulture  = (string) $this->getSetting('default_culture', 'en');

        $languageList = [];
        foreach (self::TARGET_LANGUAGES as $code => $name) {
            $languageList[] = [
                'code'    => $code,
                'name'    => $name,
                'enabled' => in_array($code, $enabledCultures, true),
                'default' => $code === $defaultCulture,
            ];
        }

        return [
            'languages'        => $languageList,
            'enabled_cultures' => $enabledCultures,
            'default_culture'  => $defaultCulture,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Adapted from Heratio TranslationController::addLanguage().
     */
    public function enableLanguage(string $code): void
    {
        $enabledJson     = $this->getSetting('i18n_languages', null);
        $enabledCultures = $enabledJson !== null ? (json_decode((string) $enabledJson, true) ?: ['en']) : ['en'];

        if (!in_array($code, $enabledCultures, true)) {
            $enabledCultures[] = $code;
            $this->setSetting('i18n_languages', json_encode($enabledCultures, JSON_THROW_ON_ERROR));
        }
    }
}
