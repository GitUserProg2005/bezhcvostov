<?php

namespace App\Processes;

use App\Enums\TaskStatus;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class NotifyParentsDeadline
{
    public function __invoke(): void
    {
        $hours = max(1, (int) config('parental.deadline_reminder_window_hours', 24));
        $windowEnd = now()->copy()->addHours($hours);

        $tasks = Task::query()
            ->where('deadline_notified', false)
            ->whereNotNull('deadline')
            ->where('status', '!=', TaskStatus::Done->value)
            ->whereBetween('deadline', [now(), $windowEnd])
            ->with(['user.childConnections.parent'])
            ->get();

        $candidates = $tasks->count();
        Log::info('NotifyParentsDeadline: start', [
            'tasks_in_window' => $candidates,
            'window_hours' => $hours,
            'window_from' => now()->toIso8601String(),
            'window_to' => $windowEnd->toIso8601String(),
        ]);

        $notificationsCreated = 0;
        $tasksMarked = 0;
        $skippedNoUser = 0;
        $tasksWithNoParents = 0;

        foreach ($tasks as $task) {
            $student = $task->user;
            if (! $student) {
                Log::warning('NotifyParentsDeadline: task without user, marking notified', [
                    'task_id' => $task->id,
                ]);
                $task->update(['deadline_notified' => true]);
                $tasksMarked++;
                $skippedNoUser++;

                continue;
            }

            $parents = $student->childConnections
                ->map(fn ($connection) => $connection->parent)
                ->filter()
                ->unique('id')
                ->values();

            if ($parents->isEmpty()) {
                Log::info('NotifyParentsDeadline: no linked parents, marking notified', [
                    'task_id' => $task->id,
                    'student_id' => $student->id,
                ]);
                $tasksWithNoParents++;
            }

            foreach ($parents as $parent) {
                Notification::query()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'deadline_soon',
                    'title' => 'Ой! Ваш ребенок не успевает!: '.$task->title,
                    'data' => [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'deadline' => $task->deadline?->toIso8601String(),
                    ],
                    'notifiable_type' => User::class,
                    'notifiable_id' => $parent->id,
                    'receiver_id' => $parent->id,
                ]);
                $notificationsCreated++;
                Log::debug('NotifyParentsDeadline: notification created', [
                    'task_id' => $task->id,
                    'parent_id' => $parent->id,
                    'deadline' => $task->deadline?->toIso8601String(),
                ]);
            }

            $task->update(['deadline_notified' => true]);
            $tasksMarked++;
        }

        Log::info('NotifyParentsDeadline: done', [
            'tasks_in_window' => $candidates,
            'tasks_marked_notified' => $tasksMarked,
            'notifications_created' => $notificationsCreated,
            'skipped_no_user' => $skippedNoUser,
            'tasks_no_parents' => $tasksWithNoParents,
        ]);
    }
}
