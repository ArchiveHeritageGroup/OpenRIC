<?php

declare(strict_types=1);

namespace OpenRiC\AI\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LLM Service — adapted from Heratio LlmService (952 lines).
 *
 * Factory and orchestrator for LLM providers (Ollama, OpenAI, Anthropic).
 * Manages configurations from llm_configs table and provides a unified
 * interface for: completion, summarization, translation, description suggestion,
 * entity extraction, and spellcheck.
 *
 * Provider dispatch:
 *   - Ollama: local, POST /api/chat or /api/generate
 *   - OpenAI: POST https://api.openai.com/v1/chat/completions
 *   - Anthropic: POST https://api.anthropic.com/v1/messages
 */
class LlmService
{
    private const ENCRYPTION_METHOD = 'aes-256-cbc';
    private const ANTHROPIC_VERSION = '2023-06-01';

    private ?string $encryptionKey = null;

    public function __construct()
    {
        $this->encryptionKey = $this->getEncryptionKey();
    }

    // =========================================================================
    // Configuration Management — from Heratio lines 32-164
    // =========================================================================

    public function getProvider(): string
    {
        $config = $this->getDefaultConfig();
        return $config->provider ?? 'ollama';
    }

    public function getConfiguration(?int $configId = null): ?object
    {
        if ($configId) {
            return DB::table('llm_configs')->where('id', $configId)->first();
        }
        return $this->getDefaultConfig();
    }

    public function getDefaultConfig(): ?object
    {
        $config = DB::table('llm_configs')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            $config = DB::table('llm_configs')
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        return $config;
    }

    public function getConfigurations(bool $activeOnly = false): array
    {
        $query = DB::table('llm_configs');
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        return $query->orderBy('provider')->orderBy('name')->get()->toArray();
    }

    public function createConfiguration(array $data): int
    {
        $insert = [
            'provider' => $data['provider'],
            'name' => $data['name'],
            'model' => $data['model'],
            'endpoint_url' => $data['endpoint_url'] ?? null,
            'max_tokens' => $data['max_tokens'] ?? 2000,
            'temperature' => $data['temperature'] ?? 0.70,
            'timeout_seconds' => $data['timeout_seconds'] ?? 120,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'options' => isset($data['options']) ? json_encode($data['options']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (!empty($data['api_key'])) {
            $insert['api_key_encrypted'] = $this->encryptApiKey($data['api_key']);
        }

        if (!empty($data['is_default'])) {
            DB::table('llm_configs')->update(['is_default' => false]);
        }

        return DB::table('llm_configs')->insertGetId($insert);
    }

    public function updateConfiguration(int $configId, array $data): bool
    {
        $update = ['updated_at' => now()];
        $fields = ['name', 'is_active', 'endpoint_url', 'model', 'max_tokens', 'temperature', 'timeout_seconds'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (array_key_exists('api_key', $data)) {
            $update['api_key_encrypted'] = !empty($data['api_key'])
                ? $this->encryptApiKey($data['api_key'])
                : null;
        }

        if (!empty($data['is_default'])) {
            DB::table('llm_configs')->update(['is_default' => false]);
            $update['is_default'] = true;
        }

        return DB::table('llm_configs')->where('id', $configId)->update($update) >= 0;
    }

    public function deleteConfiguration(int $configId): bool
    {
        return DB::table('llm_configs')->where('id', $configId)->delete() > 0;
    }

    // =========================================================================
    // LLM Operations — from Heratio lines 166-380
    // =========================================================================

    /**
     * Simple completion using default or specified config.
     */
    public function complete(string $prompt, array $options = []): ?string
    {
        $config = $this->resolveConfig($options['config_id'] ?? null);
        if (!$config) {
            Log::warning('LlmService::complete - No LLM configuration found');
            return null;
        }

        $result = $this->dispatchToProvider($config, $prompt, $options);
        return $result['success'] ? $result['text'] : null;
    }

    /**
     * Full completion with system prompt, returning detailed result.
     *
     * @return array{success: bool, text: ?string, tokens_used: int, model: ?string, generation_time_ms: int, error: ?string}
     */
    public function completeFull(string $systemPrompt, string $userPrompt, ?int $configId = null, array $options = []): array
    {
        $config = $this->resolveConfig($configId);
        if (!$config) {
            return ['success' => false, 'error' => 'No LLM configuration found', 'text' => null, 'tokens_used' => 0, 'model' => null, 'generation_time_ms' => 0];
        }

        return $this->dispatchToProviderFull($config, $systemPrompt, $userPrompt, $options);
    }

    /**
     * Summarize text to a target length.
     */
    public function summarize(string $text, int $maxLength = 200): ?string
    {
        $systemPrompt = 'You are an expert archivist. Summarize the following archival description concisely. Return ONLY the summary, no preamble.';
        $userPrompt = "Summarize the following text in approximately {$maxLength} words:\n\n{$text}";

        $result = $this->completeFull($systemPrompt, $userPrompt);
        return $result['success'] ? $result['text'] : null;
    }

    /**
     * Translate text to a target language.
     */
    public function translate(string $text, string $targetLang): ?string
    {
        $systemPrompt = "You are a professional translator. Translate the following text to {$targetLang}. Return ONLY the translation, no preamble or explanation.";
        $userPrompt = $text;

        $result = $this->completeFull($systemPrompt, $userPrompt);
        return $result['success'] ? $result['text'] : null;
    }

    /**
     * Suggest an archival description based on title and context.
     */
    public function suggestDescription(string $title, string $context = ''): ?string
    {
        $systemPrompt = 'You are an expert archivist writing scope and content notes for archival finding aids following ISAD(G) standards. Write in third person, past tense. Be factual and concise. Return ONLY the description.';
        $userPrompt = "Write a scope and content description (2-3 sentences) for an archival record titled: \"{$title}\"";
        if ($context !== '') {
            $userPrompt .= "\n\nAdditional context:\n{$context}";
        }

        $result = $this->completeFull($systemPrompt, $userPrompt);
        return $result['success'] ? $result['text'] : null;
    }

    /**
     * Extract named entities from text.
     *
     * @return array{persons: string[], organizations: string[], places: string[], dates: string[], subjects: string[]}
     */
    public function extractEntities(string $text): array
    {
        $systemPrompt = <<<'PROMPT'
You are a Named Entity Recognition (NER) system for archival descriptions. Extract all named entities from the text and categorize them.
Return a JSON object with these keys: persons, organizations, places, dates, subjects.
Each value is an array of strings. Return ONLY valid JSON, no markdown formatting.
PROMPT;

        $result = $this->completeFull($systemPrompt, $text);

        if (!$result['success'] || empty($result['text'])) {
            return ['persons' => [], 'organizations' => [], 'places' => [], 'dates' => [], 'subjects' => []];
        }

        $parsed = json_decode(trim($result['text'], " \t\n\r\0\x0B`"), true);

        if (!is_array($parsed)) {
            return ['persons' => [], 'organizations' => [], 'places' => [], 'dates' => [], 'subjects' => []];
        }

        return [
            'persons' => $parsed['persons'] ?? [],
            'organizations' => $parsed['organizations'] ?? [],
            'places' => $parsed['places'] ?? [],
            'dates' => $parsed['dates'] ?? [],
            'subjects' => $parsed['subjects'] ?? [],
        ];
    }

    /**
     * Spell and grammar check text, returning corrections.
     *
     * @return array{corrected_text: string, corrections: array}
     */
    public function spellcheck(string $text): array
    {
        $systemPrompt = <<<'PROMPT'
You are a proofreader for archival descriptions. Check the text for spelling and grammar errors.
Return a JSON object with: corrected_text (the full corrected text) and corrections (array of {original, corrected, reason}).
Return ONLY valid JSON.
PROMPT;

        $result = $this->completeFull($systemPrompt, $text);

        if (!$result['success'] || empty($result['text'])) {
            return ['corrected_text' => $text, 'corrections' => []];
        }

        $parsed = json_decode(trim($result['text'], " \t\n\r\0\x0B`"), true);

        return [
            'corrected_text' => $parsed['corrected_text'] ?? $text,
            'corrections' => $parsed['corrections'] ?? [],
        ];
    }

    // =========================================================================
    // Health & Testing — from Heratio lines 380-500
    // =========================================================================

    /**
     * Health check all active providers.
     */
    public function getAllHealth(): array
    {
        $configs = $this->getConfigurations(true);
        $results = [];

        foreach ($configs as $config) {
            $results[] = $this->checkProviderHealth($config);
        }

        return $results;
    }

    /**
     * Test a specific configuration connection.
     */
    public function testConnection(?int $configId = null): array
    {
        $config = $this->resolveConfig($configId);
        if (!$config) {
            return ['success' => false, 'error' => 'No configuration found'];
        }

        $start = microtime(true);
        $result = $this->dispatchToProvider($config, 'Say "OK" in one word.', ['max_tokens' => 10]);
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        return [
            'success' => $result['success'],
            'provider' => $config->provider,
            'model' => $config->model,
            'response_time_ms' => $elapsed,
            'response' => $result['text'] ?? null,
            'error' => $result['error'] ?? null,
        ];
    }

    // =========================================================================
    // AI Settings — from Heratio lines 500-560
    // =========================================================================

    public function getAiSetting(string $feature, string $key, ?string $default = null): ?string
    {
        $value = DB::table('settings')
            ->where('group', 'ai.' . $feature)
            ->where('key', $key)
            ->value('value');

        return $value ?? $default;
    }

    public function saveAiSetting(string $feature, string $key, ?string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'ai.' . $feature, 'key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    public function getAiSettingsByFeature(string $feature): array
    {
        return DB::table('settings')
            ->where('group', 'ai.' . $feature)
            ->pluck('value', 'key')
            ->toArray();
    }

    // =========================================================================
    // Usage Statistics — from Heratio lines 560-600
    // =========================================================================

    public function getUsageStats(): array
    {
        return [
            'configs' => DB::table('llm_configs')->where('is_active', true)->count(),
            'ner_pending' => DB::table('ner_entities')->where('status', 'pending')->count(),
            'ner_linked' => DB::table('ner_entities')->where('status', 'linked')->count(),
            'ner_total' => DB::table('ner_entities')->count(),
            'suggestions_pending' => DB::table('ai_suggestions')->where('status', 'pending')->count(),
            'suggestions_accepted' => DB::table('ai_suggestions')->where('status', 'accepted')->count(),
            'suggestions_total' => DB::table('ai_suggestions')->count(),
            'jobs_pending' => DB::table('ai_jobs')->where('status', 'pending')->count(),
            'jobs_completed' => DB::table('ai_jobs')->where('status', 'completed')->count(),
            'jobs_failed' => DB::table('ai_jobs')->where('status', 'failed')->count(),
        ];
    }

    // =========================================================================
    // Encryption — from Heratio lines 600-650
    // =========================================================================

    public function encryptApiKey(string $apiKey): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($apiKey, self::ENCRYPTION_METHOD, $this->encryptionKey, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public function decryptApiKey(string $encrypted): ?string
    {
        $data = base64_decode($encrypted);
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = substr($data, 0, $ivLength);
        $ciphertext = substr($data, $ivLength);

        $decrypted = openssl_decrypt($ciphertext, self::ENCRYPTION_METHOD, $this->encryptionKey, 0, $iv);

        return $decrypted !== false ? $decrypted : null;
    }

    // =========================================================================
    // Provider Dispatch — from Heratio lines 650-952
    // =========================================================================

    private function dispatchToProvider(object $config, string $prompt, array $options = []): array
    {
        return $this->dispatchToProviderFull($config, '', $prompt, $options);
    }

    private function dispatchToProviderFull(object $config, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $start = microtime(true);

        try {
            $result = match ($config->provider) {
                'openai' => $this->callOpenAI($config, $systemPrompt, $userPrompt, $options),
                'anthropic' => $this->callAnthropic($config, $systemPrompt, $userPrompt, $options),
                'ollama' => $this->callOllama($config, $systemPrompt, $userPrompt, $options),
                default => ['success' => false, 'error' => "Unknown provider: {$config->provider}"],
            };
        } catch (\Exception $e) {
            Log::error("LLM dispatch failed [{$config->provider}]: {$e->getMessage()}");
            $result = ['success' => false, 'error' => $e->getMessage(), 'text' => null, 'tokens_used' => 0];
        }

        $result['model'] = $config->model;
        $result['generation_time_ms'] = (int) ((microtime(true) - $start) * 1000);

        return $result;
    }

    private function callOllama(object $config, string $systemPrompt, string $userPrompt, array $options): array
    {
        $endpoint = rtrim($config->endpoint_url ?: 'http://localhost:11434', '/');

        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $response = Http::timeout($config->timeout_seconds)->post("{$endpoint}/api/chat", [
            'model' => $config->model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => (float) $config->temperature,
                'num_predict' => $options['max_tokens'] ?? $config->max_tokens,
            ],
        ]);

        if (!$response->successful()) {
            return ['success' => false, 'error' => "Ollama HTTP {$response->status()}: {$response->body()}", 'text' => null, 'tokens_used' => 0];
        }

        $data = $response->json();
        $text = $data['message']['content'] ?? $data['response'] ?? '';
        $tokens = ($data['eval_count'] ?? 0) + ($data['prompt_eval_count'] ?? 0);

        return ['success' => true, 'text' => trim($text), 'tokens_used' => $tokens];
    }

    private function callOpenAI(object $config, string $systemPrompt, string $userPrompt, array $options): array
    {
        $apiKey = $config->api_key_encrypted ? $this->decryptApiKey($config->api_key_encrypted) : null;
        if (!$apiKey) {
            return ['success' => false, 'error' => 'No API key configured for OpenAI', 'text' => null, 'tokens_used' => 0];
        }

        $endpoint = rtrim($config->endpoint_url ?: 'https://api.openai.com', '/');
        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $response = Http::withToken($apiKey)
            ->timeout($config->timeout_seconds)
            ->post("{$endpoint}/v1/chat/completions", [
                'model' => $config->model,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? $config->max_tokens,
                'temperature' => (float) $config->temperature,
            ]);

        if (!$response->successful()) {
            return ['success' => false, 'error' => "OpenAI HTTP {$response->status()}: {$response->body()}", 'text' => null, 'tokens_used' => 0];
        }

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '';
        $tokens = $data['usage']['total_tokens'] ?? 0;

        return ['success' => true, 'text' => trim($text), 'tokens_used' => $tokens];
    }

    private function callAnthropic(object $config, string $systemPrompt, string $userPrompt, array $options): array
    {
        $apiKey = $config->api_key_encrypted ? $this->decryptApiKey($config->api_key_encrypted) : null;
        if (!$apiKey) {
            return ['success' => false, 'error' => 'No API key configured for Anthropic', 'text' => null, 'tokens_used' => 0];
        }

        $endpoint = rtrim($config->endpoint_url ?: 'https://api.anthropic.com', '/');
        $body = [
            'model' => $config->model,
            'max_tokens' => $options['max_tokens'] ?? $config->max_tokens,
            'temperature' => (float) $config->temperature,
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
        ];

        if ($systemPrompt !== '') {
            $body['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::ANTHROPIC_VERSION,
            'content-type' => 'application/json',
        ])->timeout($config->timeout_seconds)->post("{$endpoint}/v1/messages", $body);

        if (!$response->successful()) {
            return ['success' => false, 'error' => "Anthropic HTTP {$response->status()}: {$response->body()}", 'text' => null, 'tokens_used' => 0];
        }

        $data = $response->json();
        $text = $data['content'][0]['text'] ?? '';
        $tokens = ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0);

        return ['success' => true, 'text' => trim($text), 'tokens_used' => $tokens];
    }

    private function checkProviderHealth(object $config): array
    {
        $start = microtime(true);

        try {
            $result = $this->dispatchToProvider($config, 'Respond with OK', ['max_tokens' => 5]);
            $elapsed = (int) ((microtime(true) - $start) * 1000);

            return [
                'config_id' => $config->id,
                'name' => $config->name,
                'provider' => $config->provider,
                'model' => $config->model,
                'healthy' => $result['success'],
                'response_time_ms' => $elapsed,
                'error' => $result['error'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'config_id' => $config->id,
                'name' => $config->name,
                'provider' => $config->provider,
                'model' => $config->model,
                'healthy' => false,
                'response_time_ms' => (int) ((microtime(true) - $start) * 1000),
                'error' => $e->getMessage(),
            ];
        }
    }

    private function resolveConfig(?int $configId): ?object
    {
        return $configId ? $this->getConfiguration($configId) : $this->getDefaultConfig();
    }

    private function getEncryptionKey(): string
    {
        $appKey = config('app.key', '');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        return hash('sha256', $appKey . 'openric-ai-encryption', true);
    }
}
