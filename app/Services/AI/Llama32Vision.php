<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Llama32Vision
{
    public function extractSchoolTaskText(string $imagePath): string
    {
        $prompt = <<<PROMPT
        Ты OCR-помощник. На входе изображение (фото учебника, тетради, доски, дневника, чата, заметки).

        ЗАДАЧА:
        - Просто считай текст с изображения и верни распознанный текст.

        КРИТИЧЕСКИ ВАЖНО:
        - Не анализируй смысл.
        - Не превращай текст в задачи.
        - Не сокращай и не перефразируй.
        - Не добавляй ничего от себя.
        - Не исправляй факты и формулировки.
        - Если фрагмент неразборчив, пропусти только его.

        ФОРМАТ ОТВЕТА:
        - Верни только чистый распознанный текст на русском, без пояснений, без markdown, без списков от модели.
        - Если текста нет, верни: "Текст не распознан".
        PROMPT;

        $result = $this->sendRequest($prompt, $imagePath, false);
        return trim((string) $result);
    }

    public function sendRequest(string $prompt, string $imagePath, bool $jsonFormat = false): mixed
    {
        $url = rtrim((string) config('services.llama_vision.url', 'http://192.168.0.52:11434'), '/').'/api/generate';
        $model = (string) config('services.llama_vision.model', 'llama3.2-vision');
        $timeout = (int) config('services.llama_vision.timeout', 600);
        
        Log::info('[Llama32Vision] request started', [
            'model' => $model,
            'json_format' => $jsonFormat,
            'prompt_length' => mb_strlen($prompt),
            'image_path' => $imagePath,
            'image_exists' => file_exists($imagePath),
            'image_readable' => is_readable($imagePath),
            'url' => $url,
            'timeout' => $timeout,
        ]);

        if (! is_readable($imagePath)) {
            Log::error('[Llama32Vision] image not readable', ['image_path' => $imagePath]);
            throw new \InvalidArgumentException("Image is not readable: {$imagePath}");
        }

        $imageBase64 = base64_encode((string) file_get_contents($imagePath));
        if ($imageBase64 === false || $imageBase64 === '') {
            Log::error('[Llama32Vision] image encode failed', ['image_path' => $imagePath]);
            throw new \RuntimeException('Unable to encode image for Llama32Vision.');
        }

        $system = $jsonFormat
            ? 'Ты AI-ассистент. Верни только строго валидный JSON без markdown и без лишнего текста.'
            : 'Ты AI-ассистент компьютерного зрения. Отвечай кратко по изображению.';

        $payload = [
            'model' => $model,
            'prompt' => $system."\n\n".$prompt,
            'images' => [$imageBase64],
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
            Log::error('[Llama32Vision] request failed', [
                'status' => $response->status(),
                'elapsed_ms' => $elapsedMs,
                'body_snippet' => mb_substr($response->body(), 0, 1000),
            ]);
            throw new \RuntimeException('Failed to send request to Llama32Vision: '.$response->body());
        }

        $content = (string) ($response->json('response') ?? '');
        Log::info('[Llama32Vision] request completed', [
            'status' => $response->status(),
            'elapsed_ms' => $elapsedMs,
            'response_length' => mb_strlen($content),
        ]);

        if (! $jsonFormat) {
            return $content;
        }

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        Log::error('[Llama32Vision] json decode failed', [
            'json_error' => json_last_error_msg(),
            'raw_snippet' => mb_substr($content, 0, 500),
        ]);
        throw new \RuntimeException('Llama32Vision JSON decode error: '.json_last_error_msg().'. Raw: '.mb_substr($content, 0, 500));
    }
}
