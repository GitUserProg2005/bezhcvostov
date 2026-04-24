<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class ProcessInsight
{
    public function __construct(
        private readonly Ollama $ollama,
    ) {
    }

    /**
     * @return array{items: array<int, array<string, mixed>>}
     */
    public function handle(string $text): array
    {
        $prompt = $this->buildPrompt($text);
        $raw = $this->ollama->sendRequest($prompt, true);

        Log::info('[ProcessInsight] raw response', [
            'type' => gettype($raw),
        ]);

        return $this->normalizeOutput($raw, $text);
    }

    private function buildPrompt(string $text): string
    {
        return <<<PROMPT
Ты помощник-аналитик урока.
Нужно преобразовать расшифровку звонка в СТРОГО валидный JSON.

Жесткие правила:
1) Верни только JSON-объект без markdown/комментариев.
2) Формат строго:
{
  "items": [
    { "type": "text", "text": "..." },
    { "type": "notes", "title": "...", "description": "..." }
  ]
}
3) Поддерживаются только type: "text" и "notes".
4) Для notes поля title и description ОБЯЗАТЕЛЬНЫ и непустые строки.
5) Для text поле text обязательно и непустая строка.
6) Никаких других полей.
7) Минимум 3 элемента, максимум 12.
8) Сначала короткий summary как text, затем 2-5 notes, затем рекомендации text.

Расшифровка звонка:
{$text}
PROMPT;
    }

    /**
     * @param mixed $raw
     * @return array{items: array<int, array<string, mixed>>}
     */
    private function normalizeOutput(mixed $raw, string $fallbackText): array
    {
        $items = [];

        if (is_array($raw)) {
            if (isset($raw['items']) && is_array($raw['items'])) {
                $items = $raw['items'];
            } elseif (array_is_list($raw)) {
                $items = $raw;
            }
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = strtolower(trim((string) ($item['type'] ?? '')));
            if ($type === 'notes') {
                $title = trim((string) ($item['title'] ?? ''));
                $description = trim((string) ($item['description'] ?? ''));
                if ($title === '' || $description === '') {
                    continue;
                }
                $normalized[] = [
                    'type' => 'notes',
                    'title' => $title,
                    'description' => $description,
                ];
                continue;
            }

            if ($type === 'text') {
                $text = trim((string) ($item['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $normalized[] = [
                    'type' => 'text',
                    'text' => $text,
                ];
            }
        }

        if (! count($normalized)) {
            $snippet = mb_substr(trim($fallbackText), 0, 400);
            $normalized = [
                [
                    'type' => 'text',
                    'text' => $snippet !== '' ? $snippet : 'Не удалось извлечь содержимое звонка.',
                ],
            ];
        }

        return ['items' => array_values($normalized)];
    }
}

