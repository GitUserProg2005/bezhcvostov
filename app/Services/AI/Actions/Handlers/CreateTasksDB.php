<?php

namespace App\Services\AI\Actions\Handlers;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CreateTasksDB
{
    public static function handle(int $userId, array $tasks, array $subtasks): ?array
    {
        DB::beginTransaction();

        try {
            $tempIdMap = [];
            $createdTasks = [];
            $createdSubtasks = [];

            // Создание задач, маппинг ID
            foreach ($tasks as $task) {
                $title = trim((string) ($task['title'] ?? ''));
                if ($title == '') {
                    continue;
                }

                $createdTask = Task::create([
                    'user_id' => $userId,
                    'title' => $title,
                    'description' => $task['description'] ?? null,
                    'difficulty' => $task['difficulty'] ?? 'medium',
                    'estimated_minutes' => $task['estimated_minutes'] ?? null,
                    'deadline' => $task['deadline'] ?? null,
                    'end_at' => $task['end_at'] ?? null,
                    'status' => $task['status'] ?? 'pending',
                ]);

                $createdTasks[] = $createdTask;
                $tempIdMap[$task['id'] ?? $createdTask->id] = $createdTask->id;
            }

            // Создание подзадач, применение маппинга ID задач
            foreach ($subtasks as $subtask) {
                $realTaskId = $tempIdMap[$subtask['task_id']] ?? null;
                $title = trim((string) ($subtask['title'] ?? ''));

                if (! $realTaskId || $title == '') {
                    continue;
                }

                $createdSubtask = Subtask::create([
                    'task_id' => $realTaskId,
                    'title' => $title,
                    'description' => $subtask['description'] ?? null,
                    'order' => $subtask['order'] ?? 1,
                    'estimated_minutes' => $subtask['estimated_minutes'] ?? null,
                    'deadline' => $subtask['deadline'] ?? null,
                    'status' => $subtask['status'] ?? 'pending',
                ]);

                $createdSubtasks[] = $createdSubtask;
            }

            // Коммит транзакции
            DB::commit();

            return [
                'tasks' => $createdTasks,
                'subtasks' => $createdSubtasks,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
