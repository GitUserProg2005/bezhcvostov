<?php

namespace App\Http\Controllers;

use App\Models\Insight;
use App\Models\Room;
use App\Services\AI\GoWhisper;
use App\Services\AI\ProcessInsight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $insights = Insight::query()
            ->where('user_id', $userId)
            ->with(['room:id,title,link_id'])
            ->latest()
            ->get()
            ->map(fn (Insight $insight) => $this->transformInsightListItem($insight))
            ->values();

        return Inertia::render('VideoRooms/Insights', [
            'insights' => $insights,
        ]);
    }

    public function show(Request $request, Insight $insight): JsonResponse
    {
        abort_if($insight->user_id !== $request->user()->id, 403);

        $insight->load('room:id,title,link_id');

        return response()->json([
            'success' => true,
            'insight' => $this->transformInsight($insight),
        ]);
    }

    public function createFromAudio(Request $request, ProcessInsight $processInsight): JsonResponse
    {
        $validated = $request->validate([
            'audio_file' => ['required', 'file', 'max:30720'],
            'link_id' => ['required', 'string', 'exists:rooms,link_id'],
        ]);

        $room = Room::query()
            ->where('link_id', $validated['link_id'])
            ->firstOrFail();

        $room->participants()->syncWithoutDetaching([$request->user()->id]);

        $audioFile = $request->file('audio_file');
        $transcribed = GoWhisper::transcribeAudio($audioFile);
        $output = $processInsight->handle($transcribed);

        $insight = Insight::create([
            'room_id' => $room->id,
            'user_id' => $request->user()->id,
            'output' => $output,
        ])->fresh(['room:id,title,link_id']);

        return response()->json([
            'success' => true,
            'insight' => $this->transformInsight($insight),
        ], 201);
    }

    private function transformInsightListItem(Insight $insight): array
    {
        $title = 'Инсайт звонка';
        foreach (($insight->output['items'] ?? []) as $item) {
            if (($item['type'] ?? null) === 'notes' && ! empty($item['title'])) {
                $title = (string) $item['title'];
                break;
            }
        }

        return [
            'id' => $insight->id,
            'title' => $title,
            'room' => [
                'title' => $insight->room?->title ?? 'Комната',
                'link_id' => $insight->room?->link_id,
            ],
            'created_at' => $insight->created_at?->toIso8601String(),
        ];
    }

    private function transformInsight(Insight $insight): array
    {
        return [
            'id' => $insight->id,
            'room' => [
                'id' => $insight->room?->id,
                'title' => $insight->room?->title ?? 'Комната',
                'link_id' => $insight->room?->link_id,
            ],
            'output' => $insight->output ?? ['items' => []],
            'created_at' => $insight->created_at?->toIso8601String(),
        ];
    }
}

