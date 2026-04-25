<?php

namespace App\Http\Controllers;

use App\Enums\ProjectType;
use App\Models\Chat;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Project/Index');
    }

    public function show(Request $request, Project $project): Response
    {
        abort_if(! $project->users()->where('users.id', $request->user()->id)->exists(), 403);

        $project->load(['users:id,name,avatar,account_code', 'chats:id,project_id,title']);

        return Inertia::render('Project/Show', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'type' => $project->type?->value ?? $project->type,
                'admin_id' => $project->admin_id,
                'users' => $project->users->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'account_code' => $user->account_code,
                ])->values(),
                'chats' => $project->chats->map(fn (Chat $chat) => [
                    'id' => $chat->id,
                    'title' => $chat->title,
                    'project_id' => $chat->project_id,
                ])->values(),
            ],
        ]);
    }

    public function getProjects(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->whereHas('users', fn ($query) => $query->where('users.id', $request->user()->id))
            ->withCount(['users', 'chats'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'type' => $project->type?->value ?? $project->type,
                'admin_id' => $project->admin_id,
                'users_count' => $project->users_count,
                'chats_count' => $project->chats_count,
            ])->values(),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_map(fn (ProjectType $type) => $type->value, ProjectType::cases()))],
        ]);

        $project = Project::query()->create([
            'title' => $validated['title'],
            'admin_id' => $request->user()->id,
            'type' => $validated['type'] ?? ProjectType::Public->value,
        ]);

        $project->users()->syncWithoutDetaching([$request->user()->id]);
        $project->chats()->create(['title' => 'Общий чат']);

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'type' => $project->type?->value ?? $project->type,
                'admin_id' => $project->admin_id,
                'users_count' => 1,
                'chats_count' => 1,
            ],
        ], 201);
    }

    public function delete(Request $request, Project $project): JsonResponse
    {
        abort_if($project->admin_id !== $request->user()->id, 403);

        $project->delete();

        return response()->json(['success' => true]);
    }

    public function addUserToProject(Request $request, Project $project): JsonResponse
    {
        abort_if($project->admin_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $project->users()->syncWithoutDetaching([$validated['user_id']]);

        return response()->json([
            'success' => true,
            'users' => $project->users()
                ->select('users.id', 'users.name', 'users.avatar', 'users.account_code')
                ->orderBy('users.name')
                ->get()
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar_url' => $member->avatar_url,
                    'account_code' => $member->account_code,
                ])->values(),
        ]);
    }

    public function searchUsers(Request $request, Project $project): JsonResponse
    {
        abort_if($project->admin_id !== $request->user()->id, 403);

        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json([]);
        }

        $alreadyInProject = $project->users()->pluck('users.id')->all();

        $users = User::query()
            ->whereNotIn('id', $alreadyInProject)
            ->whereNotNull('account_code')
            ->where('account_code', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'avatar', 'account_code']);

        return response()->json($users->map(fn (User $member) => [
            'id' => $member->id,
            'name' => $member->name,
            'avatar_url' => $member->avatar_url,
            'account_code' => $member->account_code,
        ])->values());
    }
}
