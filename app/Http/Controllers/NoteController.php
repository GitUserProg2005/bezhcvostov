<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    public function getNotes(Request $request): JsonResponse
    {
        $notes = Note::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'task_id', 'folder_id', 'updated_at']);

        return response()->json([
            'success' => true,
            'notes' => $notes,
        ]);
    }

    public function show(Request $request, Note $note): JsonResponse
    {
        if ($note->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'folder_id' => $note->folder_id,
            'task_id' => $note->task_id,
            'updated_at' => $note->updated_at?->toISOString(),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'integer', Rule::exists('folders', 'id')],
            'task_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')],
        ]);

        $user = $request->user();

        $folderId = $this->resolveFolderId($user->id, $validated['folder_id'] ?? null);

        $note = Note::query()->create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'task_id' => $validated['task_id'] ?? null,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
        ]);

        return response()->json($note, 201);
    }

    public function updateTitle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', Rule::exists('notes', 'id')],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $note = Note::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['id']);

        $note->update(['title' => $validated['title']]);

        return response()->json($note->fresh());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', Rule::exists('notes', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'integer', Rule::exists('folders', 'id')],
            'task_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')],
        ]);

        $user = $request->user();

        $note = Note::query()
            ->where('user_id', $user->id)
            ->findOrFail($validated['id']);

        $folderId = $this->resolveFolderId($user->id, $validated['folder_id'] ?? null);

        $note->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'folder_id' => $folderId,
            'task_id' => $validated['task_id'] ?? null,
        ]);

        return response()->json($note->fresh());
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', Rule::exists('notes', 'id')],
        ]);

        $note = Note::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['id']);

        $note->delete();

        return response()->json(['success' => true]);
    }

    private function resolveFolderId(int $userId, ?int $folderId): ?int
    {
        if ($folderId === null) {
            return null;
        }

        return Folder::query()
            ->where('user_id', $userId)
            ->findOrFail($folderId)
            ->id;
    }
}
