<?php

declare(strict_types=1);

namespace OpenRiC\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Handwritten Text Recognition Service — adapted from Heratio HtrService (158 lines).
 *
 * Integrates with external HTR service for extracting handwritten text
 * from archival document images. Supports batch processing, annotation
 * management, and model training.
 */
class HtrService
{
    private ?string $endpoint;

    public function __construct(
        private readonly LlmService $llm,
    ) {
        $this->endpoint = $this->llm->getAiSetting('htr', 'endpoint');
    }

    /**
     * Check HTR service health.
     */
    public function health(): array
    {
        if (empty($this->endpoint)) {
            return ['available' => false, 'error' => 'No HTR endpoint configured'];
        }

        try {
            $response = Http::timeout(5)->get("{$this->endpoint}/health");
            return [
                'available' => $response->successful(),
                'endpoint' => $this->endpoint,
                'status' => $response->status(),
                'version' => $response->json('version'),
            ];
        } catch (\Exception $e) {
            return ['available' => false, 'endpoint' => $this->endpoint, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract handwritten text from a document image.
     *
     * @param string $filePath Path to the image file
     * @param string $docType Document type: letter, register, census, form, general
     * @param string $format Output format: text, alto, hocr, page
     * @return array{success: bool, text: ?string, confidence: ?float, format: string, error: ?string}
     */
    public function extract(string $filePath, string $docType = 'general', string $format = 'text'): array
    {
        if (empty($this->endpoint)) {
            return ['success' => false, 'error' => 'No HTR endpoint configured', 'text' => null, 'confidence' => null, 'format' => $format];
        }

        try {
            $response = Http::timeout(120)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$this->endpoint}/extract", [
                    'doc_type' => $docType,
                    'format' => $format,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => "HTR HTTP {$response->status()}", 'text' => null, 'confidence' => null, 'format' => $format];
            }

            $data = $response->json();
            return [
                'success' => true,
                'text' => $data['text'] ?? null,
                'confidence' => $data['confidence'] ?? null,
                'format' => $format,
                'regions' => $data['regions'] ?? [],
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error("HTR extraction failed: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage(), 'text' => null, 'confidence' => null, 'format' => $format];
        }
    }

    /**
     * Batch process multiple files.
     *
     * @return array{job_id: ?string, queued: int, error: ?string}
     */
    public function batch(array $filePaths, string $format = 'text'): array
    {
        if (empty($this->endpoint)) {
            return ['job_id' => null, 'queued' => 0, 'error' => 'No HTR endpoint configured'];
        }

        try {
            $request = Http::timeout(30);

            foreach ($filePaths as $i => $path) {
                $request->attach("files[{$i}]", file_get_contents($path), basename($path));
            }

            $response = $request->post("{$this->endpoint}/batch", ['format' => $format]);

            if (!$response->successful()) {
                return ['job_id' => null, 'queued' => 0, 'error' => "HTR HTTP {$response->status()}"];
            }

            $data = $response->json();
            return [
                'job_id' => $data['job_id'] ?? null,
                'queued' => $data['queued'] ?? count($filePaths),
                'error' => null,
            ];
        } catch (\Exception $e) {
            return ['job_id' => null, 'queued' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Download job output.
     */
    public function downloadOutput(string $jobId, string $format = 'text'): ?string
    {
        if (empty($this->endpoint)) {
            return null;
        }

        try {
            $response = Http::timeout(30)->get("{$this->endpoint}/jobs/{$jobId}/output", ['format' => $format]);
            return $response->successful() ? $response->body() : null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Save manual annotations for training.
     */
    public function saveAnnotation(string $imagePath, string $type, array $annotations): bool
    {
        if (empty($this->endpoint)) {
            return false;
        }

        try {
            $response = Http::timeout(30)->post("{$this->endpoint}/annotate", [
                'image_path' => $imagePath,
                'type' => $type,
                'annotations' => $annotations,
            ]);
            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get training status.
     */
    public function trainingStatus(): array
    {
        if (empty($this->endpoint)) {
            return ['available' => false, 'status' => 'not_configured'];
        }

        try {
            $response = Http::timeout(10)->get("{$this->endpoint}/training/status");
            return $response->successful() ? $response->json() : ['available' => false, 'status' => 'error'];
        } catch (\Exception) {
            return ['available' => false, 'status' => 'unreachable'];
        }
    }

    /**
     * Trigger model training.
     */
    public function triggerTraining(): array
    {
        if (empty($this->endpoint)) {
            return ['success' => false, 'error' => 'No HTR endpoint configured'];
        }

        try {
            $response = Http::timeout(30)->post("{$this->endpoint}/training/start");
            return $response->successful()
                ? array_merge(['success' => true], $response->json())
                : ['success' => false, 'error' => "HTTP {$response->status()}"];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get available HTR sources and stats.
     */
    public function sources(): array
    {
        if (empty($this->endpoint)) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get("{$this->endpoint}/sources");
            return $response->successful() ? $response->json() : [];
        } catch (\Exception) {
            return [];
        }
    }
}
