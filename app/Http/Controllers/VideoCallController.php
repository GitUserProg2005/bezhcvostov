<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Room;
use App\Events\VideoCallEvent;

use Inertia\Inertia;


class VideoCallController extends Controller
{
    public function signal(Request $request) {
        \Log::info('[VideoCall] signal:request', [
            'auth_user_id' => auth()->id(),
            'link_id' => $request->input('link_id'),
            'type' => $request->input('type'),
            'has_data' => $request->has('data'),
        ]);

        $data = is_string($request->data) ? json_decode($request->data, true) : $request->data;
        \Log::info('[VideoCall] signal:parsed-data', [
            'link_id' => $request->input('link_id'),
            'type' => $request->input('type'),
            'data' => $data,
        ]);

        broadcast(new VideoCallEvent($request->link_id, [
            'type' => $request->type,
            'from' => auth()->id(),
            'data' => $data
        ]))->toOthers();

        \Log::info('[VideoCall] signal:broadcasted', [
            'link_id' => $request->input('link_id'),
            'type' => $request->input('type'),
            'from' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function index() {
        return Inertia::render('VideoRooms/Index');
    }

    public function createRoom(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        \Log::info('[VideoCall] createRoom:request', [
            'auth_user_id' => $request->user()?->id,
            'title' => $request->input('title'),
        ]);

        $room = Room::create([
            'title' => $request->title,
            'creator_id' => $request->user()->id,
        ]);

        $room->participants()->syncWithoutDetaching([auth()->id()]);

        \Log::info('[VideoCall] createRoom:created', [
            'room_id' => $room->id,
            'link_id' => $room->link_id,
            'creator_id' => $room->creator_id,
        ]);

        return response()->json([
            'success' => true,
            'room' => $room->load('creator', 'participants'),
            'linkId' => $room->link_id,
            'link' => route('video.join.room', $room->link_id)
        ]);
    }

    public function joinRoom(string $linkId) {
        \Log::info('[VideoCall] joinRoom:request', [
            'auth_user_id' => auth()->id(),
            'link_id' => $linkId,
        ]);

        $room = Room::where('link_id', $linkId)->firstOrFail();
        $room->participants()->syncWithoutDetaching([auth()->id()]);

        \Log::info('[VideoCall] joinRoom:joined', [
            'room_id' => $room->id,
            'link_id' => $room->link_id,
            'auth_user_id' => auth()->id(),
            'participants_count' => $room->participants()->count(),
        ]);
        
        return Inertia::render('VideoRooms/Room', [
            'link_id' => $linkId,
            'room' => $room->load('creator', 'participants'),
            'is_host' => $room->creator_id === auth()->id()
        ]);
    }
}
