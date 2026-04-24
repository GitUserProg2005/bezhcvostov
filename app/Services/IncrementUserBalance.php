<?php

namespace App\Services;

use App\Enums\TaskDifficulty;
use App\Enums\TaskStatus;
use App\Models\User;

class IncrementUserBalance
{
    public function handle(User $user, string $type, array $payload = []): void
    {
        $amount = match ($type) {
            'task' => $this->processTaskAction($payload),
            'note' => 2,
            'insight' => 20,
            default => 0,
        };

        if ($amount <= 0) {
            return;
        }

        $user->increment('balance', $amount);
    }

    private function processTaskAction(array $payload): int
    {
        $newStatus = $payload['new_status'] ?? null;
        $oldStatus = $payload['old_status'] ?? null;
        $difficulty = $payload['difficulty'] ?? TaskDifficulty::Medium->value;

        if ($newStatus !== TaskStatus::Done->value || $oldStatus === TaskStatus::Done->value) {
            return 0;
        }

        return match ($difficulty) {
            TaskDifficulty::Easy->value => 5,
            TaskDifficulty::Medium->value => 10,
            TaskDifficulty::Hard->value => 25,
            default => 10,
        };
    }
}
