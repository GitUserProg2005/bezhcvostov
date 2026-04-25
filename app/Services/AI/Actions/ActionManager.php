<?php

namespace App\Services\AI\Actions;

use App\Events\TaskChangeAccepted;
use App\Models\Task;
use App\Services\AI\Actions\Handlers\CreateTasksDB;
use App\Services\AI\Actions\Handlers\UpdateTasksDB;
use App\Services\AI\Gigachat;
// use App\Services\AI\Ollama;


class ActionManager
{
    public static function manage(int $userId, string $textUser): ?array
    {
        $textUser = trim($textUser);

        $prompt = <<<'PROMPT'
        Ты AI-агент для управления задачами пользователя (школьные и бытовые дела).

        Твоя задача:
        1) Понять запрос из блока «ТЕКСТ_С_ГОЛОСА».
        2) Выбрать действие: create | update | delete.
        3) Вернуть строго валидный JSON по схеме ниже.

        КРИТИЧЕСКИ ВАЖНО:
        - Используй только данные из «ТЕКСТ_С_ГОЛОСА» и «ТЕКУЩИЕ_ЗАДАЧИ».
        - Если во входном тексте нет явного "сделай/реши/прочитай", все равно создай осмысленные задачи из темы.
        - Пример: "математическая модель закона ома" => создать задачу "Изучить математическую модель закона Ома".
        - Для update/delete используй реальные id из «ТЕКУЩИЕ_ЗАДАЧИ».
        - Для create можно использовать временные id (1,2,3...), чтобы связать tasks и subtasks.
        - Обязательно пытайся дробить задачу на subtasks, если это логически возможно.
        - Если можно выделить шаги (изучить теорию, подготовить материалы, выполнить практику, проверить), создай подзадачи.
        - НЕЛЬЗЯ создавать subtasks без tasks.
        - Каждая subtask ОБЯЗАНА ссылаться на существующую task через task_id.
        - Если пользователь просит «разбить на подзадачи», сначала создай минимум 1 task, и только потом создавай subtasks для этой task.
        - Subtask не может существовать без task. Subtask отображаются только если есть task.

        КАК ВЫБИРАТЬ type:
        - create: пользователь просит добавить новые задачи ИЛИ прислал тему/формулировку, из которой можно сделать полезную задачу.
        - update: пользователь просит изменить существующие задачи (переименовать, поменять статус, дедлайн, описание, подзадачи).
        - delete: пользователь просит удалить задачу/подзадачу.

        ФОРМАТ ОТВЕТА:
        {
        "type": "create | update | delete",
        "answer": "string",
        "tasks": [],
        "subtasks": [],
        "task_ids": [],
        "subtask_ids": []
        }

        ВАЖНО ДЛЯ DELETE:
        - Для delete НЕ возвращай tasks и subtasks
        - Используй только task_ids и subtask_ids
        - Возвращай только id
        - Используй реальные id из блока «ТЕКУЩИЕ_ЗАДАЧИ»

        ВАЖНО ДЛЯ UPDATE:
        - Для update ОБЯЗАТЕЛЬНО заполняй массив tasks (и при необходимости subtasks) полными объектами по схеме.
        - Для update НЕЛЬЗЯ возвращать только task_ids/subtask_ids без tasks/subtasks.
        - Если меняется только статус, все равно верни полный объект задачи (id, title, description, difficulty, estimated_minutes, deadline, status), взяв остальные поля из «ТЕКУЩИЕ_ЗАДАЧИ».
        - Если пользователь просит перенести задачи в другой статус (pending/in_progress/done), верни в tasks все выбранные задачи с новым status и актуальными остальными полями.
        - Поле answer должно быть коротким, а сами изменения должны быть в tasks/subtasks.

        ENUM:
        - difficulty: "easy" | "medium" | "hard"
        - status: "pending" | "in_progress" | "done"

        ФОРМАТ TASK:
        { "id": number, "title": string, "description": string|null, "difficulty": "easy|medium|hard", "estimated_minutes": number|null, "deadline": "YYYY-MM-DD"|null, "status": "pending|in_progress|done" }

        ФОРМАТ SUBTASK:
        { "id": number, "task_id": number, "title": string, "description": string|null, "order": number, "estimated_minutes": number|null, "deadline": "YYYY-MM-DD"|null, "status": "pending|in_progress|done" }

        ВАЖНО ПРО ПОЛЯ:
        - Для КАЖДОГО элемента tasks/subtasks заполняй ВСЕ поля из схемы.
        - Нельзя возвращать сокращённые объекты вида {"title":"..."}.
        - Если данных нет, ставь значения по умолчанию:
        difficulty="medium", status="pending", description=null, estimated_minutes=null, deadline=null, order=1.
        - Для create id у tasks и subtasks ОБЯЗАТЕЛЕН (временный, но число).
        - Для update/delete id должен быть реальным из блока «ТЕКУЩИЕ_ЗАДАЧИ».

        Если текст короткий, но смысл понятен (например название темы) — не отказывайся, а сформируй минимум 1 задачу.
        Если смысл совсем не распознан: верни type="create", tasks=[], subtasks=[], answer="Не понял запрос, повтори проще".

        Верни только JSON без пояснений.
        PROMPT;

        $currentTasks = Task::query()
            ->where('user_id', $userId)
            ->with('subtasks')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['id', 'title', 'description', 'difficulty', 'estimated_minutes', 'deadline', 'status']);

        $currentTasksJson = $currentTasks->map(function (Task $task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'difficulty' => $task->difficulty?->value ?? $task->difficulty,
                'estimated_minutes' => $task->estimated_minutes,
                'deadline' => optional($task->deadline)->toDateString(),
                'status' => $task->status?->value ?? $task->status,
                'subtasks' => $task->subtasks->map(fn ($subtask) => [
                    'id' => $subtask->id,
                    'task_id' => $subtask->task_id,
                    'title' => $subtask->title,
                    'description' => $subtask->description,
                    'order' => $subtask->order,
                    'estimated_minutes' => $subtask->estimated_minutes,
                    'deadline' => optional($subtask->deadline)->toDateString(),
                    'status' => $subtask->status?->value ?? $subtask->status,
                ])->values()->all(),
            ];
        })->values()->all();

        $prompt .= "\n\n<<<ТЕКСТ_С_ГОЛОСА>>>\n".$textUser."\n<<<КОНЕЦ_ТЕКСТА>>>\n";
        $prompt .= "\n<<<ТЕКУЩИЕ_ЗАДАЧИ>>>\n".json_encode($currentTasksJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n<<<КОНЕЦ_ТЕКУЩИХ_ЗАДАЧ>>>\n";

        $response = app(Gigachat::class)->sendRequest($prompt, true);
        // $response = app(Ollama::class)->sendRequest($prompt, true);

        \Log::info('ActionManager response', ['response' => $response]);

        if (is_array($response)) {
            $actionType = $response['type'] ?? $response['action'] ?? 'create';

            $result = match ($actionType) {
                'create' => CreateTasksDB::handle($userId, $response['tasks'] ?? [], $response['subtasks'] ?? []),
                'update' => UpdateTasksDB::handle($userId, $response['tasks'] ?? [], $response['subtasks'] ?? []),
                // 'delete' => DeleteTaskHandler::handle($userId, $response['tasks'], $response['subtasks']),
                default => null,
            };

            if ($result !== null) {
                event(new TaskChangeAccepted($userId, $actionType, $result));
            }

            return [
                'success' => true,
                'result' => $result,
            ];
        }

        return null;
    }
}
