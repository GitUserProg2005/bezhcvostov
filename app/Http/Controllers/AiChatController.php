<?php

namespace App\Http\Controllers;

use App\Enums\MascotEmotion;
use App\Models\AiMessage;
use App\Services\AI\AiChatProcessing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function getAiMessages(Request $request): JsonResponse
    {
        $messages = AiMessage::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (AiMessage $message) => [
                'id' => $message->id,
                'sender' => $message->sender,
                'message' => $message->message,
                'emotion' => $message->emotion?->value ?? $message->emotion,
                'output' => $message->output ?? [],
                'created_at' => $message->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    public function processMessage(Request $request, AiChatProcessing $aiChatProcessing): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4000'],
        ]);

        $user = $request->user();
        $text = trim($validated['text']);

        $userMessage = AiMessage::query()->create([
            'user_id' => $user->id,
            'sender' => 'user',
            'message' => $text,
            'output' => [],
            'emotion' => MascotEmotion::Hello->value,
        ]);

        $processed = $aiChatProcessing->handle($user->id, $text);
        $output = $processed['output'] ?? [];

        $aiMessage = AiMessage::query()->create([
            'user_id' => $user->id,
            'sender' => 'ai',
            'message' => (string) ($output['text'] ?? ''),
            'output' => $output,
            'emotion' => $output['emotion'] ?? MascotEmotion::Hello->value,
        ]);

        return response()->json([
            'success' => true,
            'messages' => [
                [
                    'id' => $userMessage->id,
                    'sender' => $userMessage->sender,
                    'message' => $userMessage->message,
                    'emotion' => $userMessage->emotion?->value ?? $userMessage->emotion,
                    'output' => $userMessage->output ?? [],
                    'created_at' => $userMessage->created_at?->toIso8601String(),
                ],
                [
                    'id' => $aiMessage->id,
                    'sender' => $aiMessage->sender,
                    'message' => $aiMessage->message,
                    'emotion' => $aiMessage->emotion?->value ?? $aiMessage->emotion,
                    'output' => $aiMessage->output ?? [],
                    'created_at' => $aiMessage->created_at?->toIso8601String(),
                ],
            ],
        ]);
    }
}
