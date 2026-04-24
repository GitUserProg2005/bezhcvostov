<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Note;

use Inertia\Inertia;


class FolderController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Notes/Index');
    }

    public function tree(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $folders = Folder::query()
            ->where('user_id', $userId)
            ->defaultOrder()
            ->get(['id', 'user_id', 'title', 'parent_id', '_lft', '_rgt']);

        $notes = Note::query()
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'folder_id', 'updated_at']);

        $notesByFolder = $notes->groupBy('folder_id');

        $tree = $folders->toTree()->map(function (Folder $folder) use ($notesByFolder) {
            return $this->formatFolderNode($folder, $notesByFolder);
        })->values();

        $rootNotes = ($notesByFolder->get(null) ?? collect())
            ->map(function (Note $note) {
                return [
                    'type' => 'note',
                    'id' => $note->id,
                    'title' => $note->title,
                    'updated_at' => $note->updated_at?->toISOString(),
                ];
            })
            ->values();

        return response()->json([
            'items' => $tree->concat($rootNotes)->values(),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::exists('folders', 'id')],
        ]);

        $user = $request->user();

        $folder = new Folder([
            'user_id' => $user->id,
            'title' => $validated['title'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Folder::query()
                ->where('user_id', $user->id)
                ->findOrFail($validated['parent_id']);

            $folder->appendToNode($parent)->save();
        } else {
            $folder->saveAsRoot();
        }

        return response()->json($folder->fresh(), 201);
    }

    public function updateTitle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', Rule::exists('folders', 'id')],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $folder = Folder::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['id']);

        $folder->update(['title' => $validated['title']]);

        return response()->json($folder->fresh());
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', Rule::exists('folders', 'id')],
        ]);

        $folder = Folder::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['id']);

        $folder->delete();

        return response()->json(['success' => true]);
    }

    private function formatFolderNode(Folder $folder, $notesByFolder): array
    {
        $children = $folder->children->map(function (Folder $child) use ($notesByFolder) {
            return $this->formatFolderNode($child, $notesByFolder);
        })->values();

        $notes = ($notesByFolder->get($folder->id) ?? collect())
            ->map(function (Note $note) {
                return [
                    'type' => 'note',
                    'id' => $note->id,
                    'title' => $note->title,
                    'updated_at' => $note->updated_at?->toISOString(),
                ];
            })
            ->values();

        return [
            'type' => 'folder',
            'id' => $folder->id,
            'title' => $folder->title,
            'children' => $children->concat($notes)->values(),
        ];
    }
}
