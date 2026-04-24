<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class Gigachat {
    public function sendRequest($prompt, $jsonFormat=false, array $attachments = []): mixed {
        $accessToken = $this->getAccessToken();
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Ты AI-ассистент. Отвечай кратко и по делу.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
                'attachments' => $attachments,
            ],
        ];

        if ($jsonFormat) {
            $messages[0]['content'] = 'Ты AI-ассистент. Отвечай строго валидным JSON без markdown, без пояснений и без дополнительного текста.';
        }

        $payload = [
            'model' => 'GigaChat',
            'messages' => $messages,
            'stream' => false,
            'temperature' => 0,
        ];

        if ($jsonFormat) {
            $payload['response_format'] = [
                'type' => 'json_object',
            ];
        }

        $response = $this->requestCompletion($accessToken, $payload);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to send request' . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        if ($jsonFormat) {
            return $this->decodeJsonResponse($content);
        }

        return $content;
    }

    public function analyzeImage(string $imagePath, string $prompt, bool $jsonFormat = false): mixed
    {
        $accessToken = $this->getAccessToken();
        $fileId = $this->uploadFile($imagePath);

        $messages = [
            [
                'role' => 'system',
                'content' => $jsonFormat
                    ? 'Ты AI-ассистент. Верни только строго валидный JSON без markdown и без дополнительного текста.'
                    : 'Ты AI-ассистент компьютерного зрения. Отвечай кратко и по делу.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
                'attachments' => [$fileId],
            ],
        ];

        $payload = [
            'model' => 'GigaChat-Max',
            'messages' => $messages,
            'stream' => false,
            'temperature' => 0,
        ];

        if ($jsonFormat) {
            $payload['response_format'] = [
                'type' => 'json_object',
            ];
        }

        $response = $this->requestCompletion($accessToken, $payload);
        if (! $response->successful()) {
            throw new \Exception('Failed to analyze image: '.$response->body());
        }

        $content = (string) $response->json('choices.0.message.content');

        if ($jsonFormat) {
            return $this->decodeJsonResponse($content);
        }

        return $content;
    }

    public function uploadFile(string $imagePath): string
    {
        if (! is_readable($imagePath)) {
            throw new \InvalidArgumentException("Image is not readable: {$imagePath}");
        }

        $accessToken = $this->getAccessToken();

        $uploadUrl = (string) env('GIGACHAT_FILES_URL', 'https://gigachat.devices.sberbank.ru/api/v1/files');

        $detectedMime = mime_content_type($imagePath) ?: 'application/octet-stream';
        $mime = str_starts_with($detectedMime, 'image/') ? $detectedMime : 'image/jpeg';
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $filename = 'upload.'.$extension;

        $response = Http::withoutVerifying()
            ->connectTimeout((int) env('GIGACHAT_CONNECT_TIMEOUT', 30))
            ->timeout((int) env('GIGACHAT_TIMEOUT', 120))
            ->withHeaders([
                'Accept' => 'application/json',
                'RqUID' => (string) \Str::uuid(),
                'Authorization' => 'Bearer '.$accessToken,
            ])
            ->attach('file', file_get_contents($imagePath), $filename, [
                'Content-Type' => $mime,
            ])
            ->post($uploadUrl, [
                'purpose' => 'general',
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to upload image to GigaChat: '.$response->body());
        }

        $fileId = $response->json('content.id')
            ?? $response->json('id')
            ?? $response->json('file_id')
            ?? $response->json('data.id');

        if (! is_string($fileId) || trim($fileId) === '') {
            Log::error('[Gigachat] upload response missing file id', [
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
                'upload_url' => $uploadUrl,
            ]);
            throw new \Exception('GigaChat upload response does not contain content.id');
        }

        return $fileId;
    }

    public function getAccessToken(): string {
        return Cache::remember('gigachat_access_token', now()->addMinutes(25), function () {
            return $this->requestNewAccessToken();
        });
    }

    protected function requestNewAccessToken(): string {
        $response = Http::withoutVerifying()
            ->connectTimeout((int) env('GIGACHAT_CONNECT_TIMEOUT', 30))
            ->timeout((int) env('GIGACHAT_TIMEOUT', 120))
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'RqUID' => (string) \Str::uuid(),
                'Authorization' => 'Basic ' . env('GIGACHAT_AUTHORIZED_KEY'),
            ])
            ->asForm()
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => 'GIGACHAT_API_PERS'
            ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to get access token' . $response->body());
        }

        return $response->json('access_token');
    }

    protected function decodeJsonResponse(string $content): mixed
    {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Fallback: model may wrap JSON in markdown fences.
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;

        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Fallback: extract JSON object/array fragment from text.
        if (preg_match('/(\{.*\}|\[.*\])/s', $cleaned, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $this->repairJsonWithModel($content);
    }

    protected function requestCompletion(string $accessToken, array $payload)
    {
        return Http::withoutVerifying()
            ->connectTimeout((int) env('GIGACHAT_CONNECT_TIMEOUT', 30))
            ->timeout((int) env('GIGACHAT_TIMEOUT', 120))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', $payload);
    }

    protected function repairJsonWithModel(string $rawContent): mixed
    {
        $accessToken = $this->getAccessToken();

        $repairPayload = [
            'model' => 'GigaChat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Преобразуй входной текст в строго валидный JSON. Верни только JSON без markdown и без комментариев.',
                ],
                [
                    'role' => 'user',
                    'content' => $rawContent,
                ],
            ],
            'stream' => false,
            'temperature' => 0,
            'response_format' => [
                'type' => 'json_object',
            ],
        ];

        $repairResponse = $this->requestCompletion($accessToken, $repairPayload);
        if (!$repairResponse->successful()) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg() . '. Raw: ' . mb_substr($rawContent, 0, 500));
        }

        $repairedContent = (string) $repairResponse->json('choices.0.message.content');
        $decoded = json_decode($repairedContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg() . '. Raw: ' . mb_substr($rawContent, 0, 500));
        }

        return $decoded;
    }
}