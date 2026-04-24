<?php

namespace App\Services\AI\Actions\Handlers;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTasksDB
{
    public static function handle(int $userId, array $tasks, array $subtasks): ?array
    {
        DB::beginTransaction();

        try {
            $existingTasks = Task::query()
                ->where('user_id', $userId)
                ->get()
                ->keyBy('id');

            $existingTaskIds = $existingTasks->keys()->all();
            $existingSubtasks = Subtask::query()
                ->whereIn('task_id', $existingTaskIds)
                ->get()
                ->keyBy('id');

            $taskIdMap = [];
            $upsertedTasks = [];
            $upsertedSubtasks = [];

            foreach ($tasks as $taskData) {
                $incomingId = $taskData['id'] ?? null;
                $title = trim((string) ($taskData['title'] ?? ''));

                if ($incomingId !== null && $existingTasks->has($incomingId)) {
                    $taskModel = $existingTasks->get($incomingId);

                    if (! $taskModel) {
                        throw new \RuntimeException("Task {$incomingId} not found for update.");
                    }

                    $taskModel->update([
                        'title' => $taskData['title'] ?? $taskModel->title,
                        'description' => $taskData['description'] ?? null,
                        'difficulty' => $taskData['difficulty'] ?? $taskModel->difficulty,
                        'estimated_minutes' => $taskData['estimated_minutes'] ?? null,
                        'deadline' => $taskData['deadline'] ?? null,
                        'end_at' => $taskData['end_at'] ?? null,
                        'status' => $taskData['status'] ?? $taskModel->status,
                    ]);

                    $taskIdMap[$incomingId] = $taskModel->id;
                    $upsertedTasks[] = $taskModel->fresh();

                    continue;
                }

                if ($title === '') {
                    continue;
                }

                $createdTask = Task::create([
                    'user_id' => $userId,
                    'title' => $title,
                    'description' => $taskData['description'] ?? null,
                    'difficulty' => $taskData['difficulty'] ?? 'medium',
                    'estimated_minutes' => $taskData['estimated_minutes'] ?? null,
                    'deadline' => $taskData['deadline'] ?? null,
                    'end_at' => $taskData['end_at'] ?? null,
                    'status' => $taskData['status'] ?? 'pending',
                ]);

                if ($incomingId !== null) {
                    $taskIdMap[$incomingId] = $createdTask->id;
                }

                $upsertedTasks[] = $createdTask;
            }

            foreach ($subtasks as $subtaskData) {
                $incomingSubtaskId = $subtaskData['id'] ?? null;
                $incomingTaskId = $subtaskData['task_id'] ?? null;
                $subtaskTitle = trim((string) ($subtaskData['title'] ?? ''));

                $realTaskId = $taskIdMap[$incomingTaskId]
                    ?? ($existingTasks->has($incomingTaskId) ? $existingTasks->get($incomingTaskId)?->id : null);

                if (! $realTaskId) {
                    throw new \RuntimeException("Task {$incomingTaskId} not found for subtask binding.");
                }

                if ($incomingSubtaskId !== null && $existingSubtasks->has($incomingSubtaskId)) {
                    $subtaskModel = $existingSubtasks->get($incomingSubtaskId);

                    if (! $subtaskModel) {
                        throw new \RuntimeException("Subtask {$incomingSubtaskId} not found for update.");
                    }

                    $subtaskModel->update([
                        'task_id' => $realTaskId,
                        'title' => $subtaskData['title'] ?? $subtaskModel->title,
                        'description' => $subtaskData['description'] ?? null,
                        'order' => $subtaskData['order'] ?? $subtaskModel->order,
                        'estimated_minutes' => $subtaskData['estimated_minutes'] ?? null,
                        'deadline' => $subtaskData['deadline'] ?? null,
                        'status' => $subtaskData['status'] ?? $subtaskModel->status,
                    ]);

                    $upsertedSubtasks[] = $subtaskModel->fresh();

                    continue;
                }

                if ($subtaskTitle === '') {
                    continue;
                }

                $createdSubtask = Subtask::create([
                    'task_id' => $realTaskId,
                    'title' => $subtaskTitle,
                    'description' => $subtaskData['description'] ?? null,
                    'order' => $subtaskData['order'] ?? 1,
                    'estimated_minutes' => $subtaskData['estimated_minutes'] ?? null,
                    'deadline' => $subtaskData['deadline'] ?? null,
                    'status' => $subtaskData['status'] ?? 'pending',
                ]);

                $upsertedSubtasks[] = $createdSubtask;
            }

            DB::commit();

            return [
                'tasks' => $upsertedTasks,
                'subtasks' => $upsertedSubtasks,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
