<?php

namespace App\Services\AI;

use App\Enums\AdditionalActions;
use App\Enums\MascotEmotion;
use App\Services\AI\Actions\ActionManager;

class AiChatProcessing
{
    public function __construct(
        private readonly Gigachat $gigachat,
    ) {
    }

    /**
     * @return array{
     *   output: array{
     *     text: string,
     *     link: string|null,
     *     buttons: array<int, array{title: string, link: string}>,
     *     emotion: string,
     *     additional_commands: string,
     *     additional_commands_result: mixed
     *   }
     * }
     */
    public function handle(int $userId, string $text): array
    {
        $prompt = $this->buildPrompt($text);
        $raw = $this->gigachat->sendRequest($prompt, true);
        \Log::info('AICHAT PROCESSING: ', $raw);
        $output = $this->normalizeOutput($raw);

        $commandResult = null;
        $command = AdditionalActions::tryFrom($output['additional_commands'] ?? '');

        $commandResult = match ($command) {
            AdditionalActions::Tasks => ActionManager::manage($userId, $text),
            default => null,
        };

        $output['additional_commands_result'] = $commandResult;

        return ['output' => $output];
    }

    private function buildPrompt(string $text): string
    {
        $routes = [
            'tasks.index' => route('tasks.index'),
            'notes.get' => route('notes.get'),
            'folders.index' => route('folders.index'),
            'video.index.room' => route('video.index.room'),
            'video.insights.index' => route('video.insights.index'),
        ];

        $routesJson = json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
        Ты маскот-помощник по имени Хвостик.
        Общайся вежливо, дружелюбно и понятно для школьников и учителей.
        Не задавай много уточняющих вопросов.
        Если пользователь просит создать/обновить задачи, используй его текст как готовую инструкцию и переходи к действию.
        Не спрашивай подтверждение перед выполнением.
        Верни строго валидный JSON-объект без markdown и без дополнительного текста.

        Формат строго:
        {
          "output": {
            "text": "string",
            "link": "string|null",
            "buttons": [
              { "title": "string", "link": "route_name_or_url" }
            ],
            "emotion": "hello|jump|maskot_with_pen|with_notebook|with_panel",
            "additional_commands": "tasks"
          }
        }

        Правила:
        1) Поле output обязательно.
        2) output.text должен быть непустой строкой.
        3) output.link может быть null, route name или полный URL.
        4) output.buttons — массив (может быть пустым), максимум 4 кнопки.
        5) Каждая кнопка содержит title и link.
        6) additional_commands либо "tasks", либо пустая строка.
        7) Если пользователь просит помощь с задачами (создать/изменить/разбить и т.д.) ставь additional_commands="tasks".
        8) Используй только эти route имена/ссылки, если даешь навигацию:
        {$routesJson} 
        9) Не задавай лишние уточнения про задачи и не откладывай выполнение на потом.
        10) additional_commands добавляется только когда пользователь явно просит действие по задачам.

        Запрос пользователя:
        {$text}
        PROMPT;
    }

    /**
     * @param mixed $raw
     * @return array{text: string, link: string|null, buttons: array<int, array{title: string, link: string}>, emotion: string, additional_commands: string}
     */
    private function normalizeOutput(mixed $raw): array
    {
        $output = is_array($raw) && isset($raw['output']) && is_array($raw['output'])
            ? $raw['output']
            : [];

        $text = trim((string) ($output['text'] ?? ''));
        if ($text === '') {
            $text = 'Привет! Я Хвостик. Чем могу помочь?';
        }

        $linkValue = $output['link'] ?? null;
        $link = is_string($linkValue) && trim($linkValue) !== '' ? trim($linkValue) : null;

        $buttonsRaw = is_array($output['buttons'] ?? null) ? $output['buttons'] : [];
        $buttons = [];
        foreach ($buttonsRaw as $button) {
            if (! is_array($button)) {
                continue;
            }

            $title = trim((string) ($button['title'] ?? ''));
            $buttonLink = trim((string) ($button['link'] ?? ''));
            if ($title === '' || $buttonLink === '') {
                continue;
            }

            $buttons[] = [
                'title' => $title,
                'link' => $buttonLink,
            ];
        }

        $emotion = MascotEmotion::tryFrom((string) ($output['emotion'] ?? ''))
            ?->value ?? MascotEmotion::Hello->value;

        $additionalCommands = AdditionalActions::tryFrom((string) ($output['additional_commands'] ?? ''))
            ?->value ?? '';

        return [
            'text' => $text,
            'link' => $link,
            'buttons' => array_values($buttons),
            'emotion' => $emotion,
            'additional_commands' => $additionalCommands,
        ];
    }
}
