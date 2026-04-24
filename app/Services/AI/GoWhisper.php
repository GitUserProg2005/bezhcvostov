<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GoWhisper {
    public static function transcribeAudio($audioBlob): string {
        try {
            $transcription = self::requestTranscription($audioBlob, (string) config('services.whisper.language', 'ru'));

            // Если качество распознавания странное (мало кириллицы) — пробуем альтернативный language.
            if (self::looksLikeWrongLanguage($transcription)) {
                Log::warning('Whisper suspicious transcription, retrying with alternative language', [
                    'preview' => mb_substr($transcription, 0, 120),
                ]);
                $retry = self::requestTranscription($audioBlob, 'russian');
                if (! self::looksLikeWrongLanguage($retry) || mb_strlen($retry) > mb_strlen($transcription)) {
                    $transcription = $retry;
                }
            }

            Log::info('Transcription completed', [
                'text_length' => mb_strlen($transcription),
                'first_100_chars' => mb_substr($transcription, 0, 100),
            ]);

            return $transcription;
        } catch (\Exception $e) {
            Log::error('Whisper API error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private static function requestTranscription($audioBlob, string $language): string
    {
        $timeout = (int) config('services.whisper.timeout', 90);
        $model = (string) config('services.whisper.model', 'small');
        $url = rtrim((string) config('services.whisper.url'), '/').'/asr';

        $response = Http::timeout($timeout)
            ->retry(2, 1200)
            ->attach(
                'audio_file',
                file_get_contents($audioBlob->getRealPath()),
                'audio.webm',
                ['Content-Type' => 'audio/webm']
            )
            ->post($url, [
                'model' => $model,
                'language' => $language,
                'task' => 'transcribe',
                'translate' => false,
                'temperature' => 0,
                'initial_prompt' => 'Распознавай только русскую речь. Сохраняй слова дословно.',
            ]);

        if ($response->failed()) {
            Log::error('Whisper API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'language' => $language,
                'model' => $model,
            ]);
            throw new \Exception('Whisper API error: '.$response->status());
        }

        $body = trim((string) $response->body());
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $text = trim((string) ($json['text'] ?? $json['transcription'] ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        return $body;
    }

    private static function looksLikeWrongLanguage(string $text): bool
    {
        $clean = trim($text);
        if ($clean === '') {
            return true;
        }

        $letters = preg_replace('/[^[:alpha:]]/u', '', $clean) ?? '';
        $total = mb_strlen($letters);
        if ($total === 0) {
            return true;
        }

        preg_match_all('/[А-Яа-яЁё]/u', $letters, $ruMatches);
        $ruCount = count($ruMatches[0]);
        $ratio = $ruCount / max($total, 1);

        return $ratio < 0.35;
    }
}