<?php

declare(strict_types=1);

namespace OpenRiC\Translation\Contracts;

/**
 * Translation service interface.
 *
 * Adapted from Heratio ahg-translation (652-line controller + service helpers).
 * Provides machine translation via NLLB-200 endpoint, draft management,
 * field mapping for RiC entities, health checking, deduplication, and logging.
 */
interface TranslationServiceInterface
{
    // ── MT Core ─────────────────────────────────────────────────────────

    /**
     * Translate text via the configured MT endpoint (NLLB-200).
     *
     * @param  string   $text           Source text to translate
     * @param  string   $sourceCulture  ISO 639-1/3 source language code
     * @param  string   $targetCulture  ISO 639-1/3 target language code
     * @param  int|null $maxLength      Optional max output length
     * @return array{ok: bool, translation: ?string, http_status: ?int, error: ?string, elapsed_ms: int, endpoint: string, model: ?string}
     */
    public function translateText(string $text, string $sourceCulture, string $targetCulture, ?int $maxLength = null): array;

    /**
     * Health-check the MT endpoint with a short test phrase.
     *
     * @return array{ok: bool, endpoint: string, http_status: int, curl_error: ?string}
     */
    public function healthCheck(): array;

    // ── Draft Management ────────────────────────────────────────────────

    /**
     * Create a translation draft record. Deduplicates by source hash.
     *
     * @return array{ok: bool, draft_id: ?int, deduped: bool, error: ?string}
     */
    public function createDraft(
        string $entityIri,
        string $fieldName,
        string $sourceCulture,
        string $targetCulture,
        string $sourceText,
        string $translatedText,
        ?int $userId = null,
    ): array;

    /**
     * Apply a translation draft to the entity.
     *
     * @return array{ok: bool, culture: ?string, error: ?string}
     */
    public function applyDraft(int $draftId, bool $overwrite = false, ?string $targetCulture = null): array;

    /**
     * Reject a translation draft.
     */
    public function rejectDraft(int $draftId, ?int $userId = null): bool;

    /**
     * Get a single draft by ID.
     */
    public function getDraft(int $draftId): ?array;

    /**
     * List drafts for an entity IRI, optionally filtered by status.
     *
     * @return array[]
     */
    public function getDraftsForEntity(string $entityIri, ?string $status = null): array;

    // ── Logging ─────────────────────────────────────────────────────────

    /**
     * Log a translation attempt (success or failure).
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
    ): void;

    /**
     * Get translation log entries for an entity.
     *
     * @return array[]
     */
    public function getLogForEntity(string $entityIri, int $limit = 50): array;

    // ── Settings ────────────────────────────────────────────────────────

    /**
     * Get a translation setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed;

    /**
     * Set (upsert) a translation setting.
     */
    public function setSetting(string $key, string $value): void;

    // ── Field Mapping ───────────────────────────────────────────────────

    /**
     * Get all translatable field keys with labels.
     *
     * @return array<string, string>
     */
    public function getTranslatableFields(): array;

    /**
     * Get the max length for a given field key.
     */
    public function getFieldMaxLength(string $fieldKey): int;

    // ── Language Support ────────────────────────────────────────────────

    /**
     * Get all supported target languages.
     *
     * @return array<string, string> code => name
     */
    public function getTargetLanguages(): array;

    /**
     * Get languages enabled in the system.
     *
     * @return array{languages: array[], enabled_cultures: string[], default_culture: string}
     */
    public function getEnabledLanguages(): array;

    /**
     * Enable a language in the system.
     */
    public function enableLanguage(string $code): void;
}
