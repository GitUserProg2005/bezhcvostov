<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request, Project $project, Chat $chat): Response
    {
        abort_if($chat->project_id !== $project->id, 404);
        abort_if(! $project->users()->where('users.id', $request->user()->id)->exists(), 403);

        return Inertia::render('Project/Chat', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
            ],
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'project_id' => $chat->project_id,
            ],
        ]);
    }

    public function getMessages(Request $request, Project $project, Chat $chat): JsonResponse
    {
        abort_if($chat->project_id !== $project->id, 404);
        abort_if(! $project->users()->where('users.id', $request->user()->id)->exists(), 403);

        $messages = Message::query()
            ->where('chat_id', $chat->id)
            ->with('user:id,name,avatar,account_code')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn (Message $message) => [
                'id' => $message->id,
                'content' => $message->content,
                'chat_id' => $message->chat_id,
                'created_at' => $message->created_at?->toIso8601String(),
                'user' => $message->user ? [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar_url' => $message->user->avatar_url,
                ] : null,
            ])->values(),
        ]);
    }

    public function addMessage(Request $request, Project $project, Chat $chat): JsonResponse
    {
        abort_if($chat->project_id !== $project->id, 404);
        abort_if(! $project->users()->where('users.id', $request->user()->id)->exists(), 403);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $message = Message::query()->create([
            'content' => $validated['content'],
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
        ]);

        $message->load('user:id,name,avatar,account_code');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'chat_id' => $message->chat_id,
                'created_at' => $message->created_at?->toIso8601String(),
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar_url' => $message->user->avatar_url,
                ],
            ],
        ], 201);
    }
}
