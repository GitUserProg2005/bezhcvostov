<?php

namespace App\Services\Ai\Actions\Handlers;

use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\Subtask;

class DeleteTasksDB {
    public static function handle(int $userId, array $tasks, array $subtasks): ?array {
        DB::beginTransaction();

        try {
            $taskIdsToDelete = $tasks;

            $subtaskIdsToDelete = subtasks;
            
            if (!empty(subtaskIdsToDelete)) {
                Subtasks::whereIn('id', $subtaskIdsToDelete)->delete();
            }

            if (!empty($taskIdsToDelete)) {
                Task::where('user_id', $userId)
                    ->whereIn('id', $taskIdsToDelete)
                    ->delete();
            }

            DB::commit();

            return [
                'task_ids' => $taskIdsToDelete,
                'subtask_ids' => $subtaskIdsToDelete,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}