<?php

namespace App\Http\Controllers;

use App\Enums\TaskDifficulty;
use App\Enums\TaskSourceType;
use App\Enums\TaskStatus;
use App\Models\Subtask;
use App\Models\Task;
use App\Services\AI\Actions\ActionManager;
use App\Services\AI\GoWhisper;
use App\Services\AI\Llama32Vision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
// use App\Services\AI\Gigachat;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Task/Index');
    }

    public function show(Request $request, Task $task): Response
    {
        abort_if($task->user_id !== $request->user()->id, 403);

        $task->load(['subtasks', 'inputs']);

        return Inertia::render('Task/Task', [
            'task' => $this->transformTask($task),
        ]);
    }

    public function getTasks(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tasks')) {
            return response()->json([
                'success' => true,
                'tasks' => [],
            ]);
        }

        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->with(['subtasks', 'inputs'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Task $task) => $this->transformTask($task));

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
        ]);
    }

    public function createFormTasks(Request $request): JsonResponse
    {
        $validated = $request->validate($this->createFormTaskRules());

        $task = DB::transaction(function () use ($request, $validated) {
            $task = Task::create([
                'user_id' => $request->user()->id,
                'title' => $validated['task']['title'],
                'description' => $validated['task']['description'] ?? null,
                'difficulty' => $validated['task']['difficulty'] ?? TaskDifficulty::Medium->value,
                'estimated_minutes' => $validated['task']['estimated_minutes'] ?? null,
                'deadline' => $validated['task']['deadline'] ?? null,
                'end_at' => $validated['task']['end_at'] ?? null,
                'status' => $validated['task']['status'] ?? TaskStatus::Pending->value,
                'source_type' => $validated['task']['source_type'] ?? TaskSourceType::Text->value,
                'ai_generated' => $validated['task']['ai_generated'] ?? false,
            ]);

            $this->storeTaskInput($request, $task, $validated);

            foreach ($validated['subtasks'] ?? [] as $subtaskData) {
                $task->subtasks()->create([
                    'title' => $subtaskData['title'],
                    'description' => $subtaskData['description'] ?? null,
                    'order' => $subtaskData['order'] ?? 0,
                    'estimated_minutes' => $subtaskData['estimated_minutes'] ?? null,
                    'deadline' => $subtaskData['deadline'] ?? null,
                    'status' => $subtaskData['status'] ?? TaskStatus::Pending->value,
                ]);
            }

            return $task->fresh(['subtasks', 'inputs']);
        });

        return response()->json([
            'success' => true,
            'task' => $this->transformTask($task),
        ], 201);
    }

    public function createViaVoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio_file' => ['required', 'file', 'max:15360'],
        ]);

        $audioFile = $request->file('audio_file');
        $audioFilePath = $audioFile->store('tasks/inputs', 's3');

        $audioText = GoWhisper::transcribeAudio($audioFile);

        $actionManager = ActionManager::manage($request->user()->id, $audioText);

        if ($actionManager['success']) {
            return response()->json([
                'success' => true,
                'audio_text' => $audioText,
                'result' => $actionManager['result'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'audio_text' => $audioText,
                'result' => $actionManager['result'],
            ]);
        }
    }

    public function createViaPhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image_file' => ['required', 'image', 'max:15360'],
        ]);

        $imageFile = $request->file('image_file');
        $imagePath = $imageFile->store('tasks/inputs', 's3');

        // For Llama3.2-Vision we pass a local temp file path.
        $localImagePath = $imageFile->getRealPath();
        if (! $localImagePath) {
            return response()->json([
                'success' => false,
                'message' => 'Не удалось прочитать изображение.',
            ], 422);
        }

        // $photoText = app(Gigachat::class)->analyzeImage(...);
        $photoText = app(Llama32Vision::class)->extractSchoolTaskText($localImagePath);

        $actionManager = ActionManager::manage($request->user()->id, (string) $photoText);

        if ($actionManager['success']) {
            return response()->json([
                'success' => true,
                'photo_text' => $photoText,
                'image_path' => $imagePath,
                'result' => $actionManager['result'],
            ]);
        }

        return response()->json([
            'success' => false,
            'photo_text' => $photoText,
            'image_path' => $imagePath,
            'result' => $actionManager['result'] ?? null,
        ], 422);
    }

    public function deleteTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
        ]);

        Task::query()
            ->where('id', $validated['task_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => true]);
    }

    public function updateTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'difficulty' => ['nullable', Rule::in($this->difficultyValues())],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in($this->taskStatusValues())],
            'source_type' => ['nullable', Rule::in($this->sourceTypeValues())],
            'ai_generated' => ['nullable', 'boolean'],
        ]);

        $task = Task::query()
            ->where('id', $validated['task_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'difficulty' => $validated['difficulty'] ?? $task->difficulty,
            'estimated_minutes' => $validated['estimated_minutes'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'status' => $validated['status'] ?? $task->status,
            'source_type' => $validated['source_type'] ?? $task->source_type,
            'ai_generated' => $validated['ai_generated'] ?? $task->ai_generated,
        ]);

        return response()->json([
            'success' => true,
            'task' => $this->transformTask($task->fresh(['subtasks', 'inputs'])),
        ]);
    }

    public function updateTaskStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
            'status' => ['required', Rule::in($this->taskStatusValues())],
        ]);

        $task = Task::query()
            ->where('id', $validated['task_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $task->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'task' => $this->transformTask($task->fresh(['subtasks', 'inputs'])),
        ]);
    }

    public function updateSubtask(Request $request): JsonResponse
    {
        $validated = $request->validate($this->subtaskRules(false));

        $subtask = Subtask::query()
            ->where('id', $validated['subtask_id'])
            ->whereHas('task', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();

        $subtask->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'estimated_minutes' => $validated['estimated_minutes'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'status' => $validated['status'] ?? TaskStatus::Pending,
        ]);

        return response()->json([
            'success' => true,
            'subtask' => $this->transformSubtask($subtask->fresh()),
        ]);
    }

    public function deleteSubtask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtask_id' => ['required', 'integer'],
        ]);

        Subtask::query()
            ->where('id', $validated['subtask_id'])
            ->whereHas('task', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => true]);
    }

    public function updateSubtaskStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtask_id' => ['required', 'integer'],
            'status' => ['required', Rule::in($this->taskStatusValues())],
        ]);

        $subtask = Subtask::query()
            ->where('id', $validated['subtask_id'])
            ->whereHas('task', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();

        $subtask->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'subtask' => $this->transformSubtask($subtask->fresh()),
        ]);
    }

    public function setSubtaskDone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtask_id' => ['required', 'integer'],
            'done' => ['nullable', 'boolean'],
        ]);

        $subtask = Subtask::query()
            ->where('id', $validated['subtask_id'])
            ->whereHas('task', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();

        $isDone = (bool) ($validated['done'] ?? true);

        $subtask->update([
            'status' => $isDone ? TaskStatus::Done->value : TaskStatus::Pending->value,
        ]);

        return response()->json([
            'success' => true,
            'subtask' => $this->transformSubtask($subtask->fresh()),
        ]);
    }

    private function createFormTaskRules(): array
    {
        return [
            'task' => ['required', 'array'],
            'task.title' => ['required', 'string', 'max:255'],
            'task.description' => ['nullable', 'string'],
            'task.difficulty' => ['nullable', Rule::in($this->difficultyValues())],
            'task.estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'task.deadline' => ['nullable', 'date'],
            'task.end_at' => ['nullable', 'date'],
            'task.status' => ['nullable', Rule::in($this->taskStatusValues())],
            'task.source_type' => ['nullable', Rule::in($this->sourceTypeValues())],
            'task.ai_generated' => ['nullable', 'boolean'],

            'subtasks' => ['nullable', 'array'],
            'subtasks.*.title' => ['required', 'string', 'max:255'],
            'subtasks.*.description' => ['nullable', 'string'],
            'subtasks.*.order' => ['nullable', 'integer', 'min:0'],
            'subtasks.*.estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'subtasks.*.deadline' => ['nullable', 'date'],
            'subtasks.*.status' => ['nullable', Rule::in($this->taskStatusValues())],

            'task_input.type' => ['nullable', Rule::in(['voice', 'image', 'text'])],
            'task_input.file' => ['nullable', 'file', 'max:15360'],
            'task_input.raw_text' => ['nullable', 'string'],
            'task_input.processed_text' => ['nullable', 'string'],
        ];
    }

    private function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'difficulty' => ['nullable', Rule::in($this->difficultyValues())],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in($this->taskStatusValues())],
            'source_type' => ['nullable', Rule::in($this->sourceTypeValues())],
            'ai_generated' => ['nullable', 'boolean'],
            'task_input.type' => ['nullable', Rule::in(['voice', 'image', 'text'])],
            'task_input.file' => ['nullable', 'file', 'max:15360'],
            'task_input.raw_text' => ['nullable', 'string'],
            'task_input.processed_text' => ['nullable', 'string'],
        ];
    }

    private function subtaskRules(bool $isCreate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in($this->taskStatusValues())],
        ];

        if ($isCreate) {
            $rules['task_id'] = ['required', 'integer'];
        } else {
            $rules['subtask_id'] = ['required', 'integer'];
        }

        return $rules;
    }

    private function storeTaskInput(Request $request, Task $task, array $validated): void
    {
        $input = $validated['task_input'] ?? [];
        $hasFile = $request->hasFile('task_input.file');

        if (! $hasFile && empty(array_filter($input, fn ($value) => $value !== null && $value !== ''))) {
            return;
        }

        $filePath = null;
        if ($hasFile) {
            $filePath = $request->file('task_input.file')->store('tasks/inputs', 's3');
        }

        $task->inputs()->create([
            'type' => $input['type'] ?? 'text',
            'file_path' => $filePath,
            'raw_text' => $input['raw_text'] ?? null,
            'processed_text' => $input['processed_text'] ?? null,
        ]);
    }

    private function difficultyValues(): array
    {
        return array_map(fn (TaskDifficulty $difficulty) => $difficulty->value, TaskDifficulty::cases());
    }

    private function taskStatusValues(): array
    {
        return array_map(fn (TaskStatus $status) => $status->value, TaskStatus::cases());
    }

    private function sourceTypeValues(): array
    {
        return array_map(fn (TaskSourceType $sourceType) => $sourceType->value, TaskSourceType::cases());
    }

    private function transformTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'user_id' => $task->user_id,
            'title' => $task->title,
            'description' => $task->description,
            'difficulty' => $task->difficulty?->value,
            'estimated_minutes' => $task->estimated_minutes,
            'deadline' => $task->deadline?->toIso8601String(),
            'end_at' => $task->end_at?->format('Y-m-d'),
            'status' => $task->status?->value,
            'source_type' => $task->source_type?->value,
            'ai_generated' => (bool) $task->ai_generated,
            'subtasks' => $task->subtasks
                ->sortBy('order')
                ->values()
                ->map(fn (Subtask $subtask) => $this->transformSubtask($subtask))
                ->values(),
            'inputs' => $task->inputs->map(fn ($input) => [
                'id' => $input->id,
                'type' => $input->type,
                'file_path' => $input->file_path,
                'file_url' => $input->file_url,
                'raw_text' => $input->raw_text,
                'processed_text' => $input->processed_text,
                'created_at' => $input->created_at?->toIso8601String(),
            ])->values(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    private function transformSubtask(Subtask $subtask): array
    {
        return [
            'id' => $subtask->id,
            'task_id' => $subtask->task_id,
            'title' => $subtask->title,
            'description' => $subtask->description,
            'order' => $subtask->order,
            'estimated_minutes' => $subtask->estimated_minutes,
            'deadline' => $subtask->deadline?->toIso8601String(),
            'status' => $subtask->status?->value,
            'created_at' => $subtask->created_at?->toIso8601String(),
            'updated_at' => $subtask->updated_at?->toIso8601String(),
        ];
    }
}
