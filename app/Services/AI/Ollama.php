<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class Ollama
{
    public function sendRequest(string $prompt, bool $jsonFormat = false): mixed
    {
        $model = (string) config('services.ollama.model', 'qwen2.5:7b');
        $baseUrl = rtrim((string) config('services.ollama.url', 'http://192.168.0.52:11434'), '/');
        $url = $baseUrl.'/api/generate';
        $timeout = (int) config('services.ollama.timeout', 180);

        Log::info('[Ollama] request started', [
            'model' => $model,
            'json_format' => $jsonFormat,
            'prompt_length' => mb_strlen($prompt),
            'url' => $url,
            'timeout' => $timeout,
        ]);

        $system = $jsonFormat
            ? 'Ты AI-ассистент. Отвечай строго валидным JSON без markdown, без пояснений и без дополнительного текста.'
            : 'Ты AI-ассистент. Отвечай кратко и по делу.';

        $payload = [
            'model' => $model,
            'prompt' => $system."\n\n".$prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0,
            ],
        ];

        if ($jsonFormat) {
            $payload['format'] = 'json';
        }

        $startedAt = microtime(true);
        $response = Http::timeout($timeout)->post($url, $payload);
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $body = (string) $response->body();
            Log::error('[Ollama] request failed', [
                'status' => $response->status(),
                'elapsed_ms' => $elapsedMs,
                'body_snippet' => mb_substr($body, 0, 1000),
            ]);

            // Helpful hint when model isn't pulled on target machine.
            if ($response->status() === 404 && str_contains($body, 'model') && str_contains($body, 'not found')) {
                $available = $this->fetchAvailableModels($baseUrl, $timeout);
                throw new \RuntimeException(
                    'Ollama model "'.$model.'" not found. Available: '.implode(', ', $available)
                    .'. Pull needed model or change OLLAMA_MODEL.'
                );
            }

            throw new \RuntimeException('Failed to send request to Ollama: '.$body);
        }

        $content = (string) ($response->json('response') ?? '');
        Log::info('[Ollama] request completed', [
            'status' => $response->status(),
            'elapsed_ms' => $elapsedMs,
            'response_length' => mb_strlen($content),
        ]);

        if (! $jsonFormat) {
            return $content;
        }

        return $this->decodeJsonResponse($content);
    }

    protected function decodeJsonResponse(string $content): mixed
    {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $cleaned = trim($content);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;

        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (preg_match('/(\{.*\}|\[.*\])/s', $cleaned, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        throw new \RuntimeException('JSON decode error: '.json_last_error_msg().'. Raw: '.mb_substr($content, 0, 500));
    }

    /**
     * @return array<int, string>
     */
    protected function fetchAvailableModels(string $baseUrl, int $timeout): array
    {
        try {
            $response = Http::timeout(min($timeout, 20))->get($baseUrl.'/api/tags');
            if (! $response->successful()) {
                return [];
            }

            $models = $response->json('models') ?? [];
            if (! is_array($models)) {
                return [];
            }

            return array_values(array_filter(array_map(
                fn ($model) => is_array($model) ? ($model['name'] ?? null) : null,
                $models
            )));
        } catch (\Throwable) {
            return [];
        }
    }
}
