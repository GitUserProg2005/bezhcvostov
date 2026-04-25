<?php

namespace App\Http\Controllers;

use App\Enums\ControlRequestStatus;
use App\Enums\UserRole;
use App\Models\Connection;
use App\Models\ControlRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ControlRequestController extends Controller
{
    public function searchByAccountCode(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== UserRole::Parent) {
            abort(403);
        }

        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $users = User::query()
            ->where('role', UserRole::Student)
            ->whereNotNull('account_code')
            ->where('account_code', 'like', '%'.$q.'%')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'account_code', 'role', 'avatar']);

        return response()->json(
            $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'account_code' => $u->account_code,
                'role' => $u->role?->value,
                'avatar_url' => $u->avatar_url,
            ])->values()->all()
        );
    }

    public function createControlRequest(Request $request): JsonResponse
    {
        $parent = $request->user();
        if (! $parent || $parent->role !== UserRole::Parent) {
            abort(403);
        }

        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'not_in:'.$parent->id],
        ]);

        $receiver = User::query()->findOrFail($validated['receiver_id']);
        if ($receiver->role !== UserRole::Student) {
            return response()->json(['message' => 'Запрос можно отправить только ученику.'], 422);
        }

        $existing = ControlRequest::query()
            ->where('sender_id', $parent->id)
            ->where('receiver_id', $receiver->id)
            ->where('status', ControlRequestStatus::Pending)
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'Запрос уже отправлен и ожидает ответа.'], 422);
        }

        $connected = Connection::query()
            ->where('parent_id', $parent->id)
            ->where('child_id', $receiver->id)
            ->exists();

        if ($connected) {
            return response()->json(['message' => 'Связь с этим учеником уже установлена.'], 422);
        }

        $controlRequest = ControlRequest::query()->create([
            'sender_id' => $parent->id,
            'receiver_id' => $receiver->id,
            'status' => ControlRequestStatus::Pending,
        ]);

        return response()->json([
            'request' => $controlRequest->load(['receiver:id,name,account_code']),
        ], 201);
    }

    /**
     * Входящие заявки со статусом pending (для ученика).
     */
    public function pendingReceived(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== UserRole::Student) {
            abort(403);
        }

        $requests = ControlRequest::query()
            ->where('receiver_id', $user->id)
            ->where('status', ControlRequestStatus::Pending)
            ->with(['sender:id,name,email,avatar,account_code,role'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    public function updateStatusRequest(Request $request, ControlRequest $controlRequest): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== UserRole::Student) {
            abort(403);
        }

        if ($controlRequest->receiver_id !== $user->id) {
            abort(403);
        }

        if ($controlRequest->status !== ControlRequestStatus::Pending) {
            return response()->json(['message' => 'Заявка уже обработана.'], 422);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    ControlRequestStatus::Approved->value,
                    ControlRequestStatus::Refused->value,
                ]),
            ],
        ]);

        $newStatus = ControlRequestStatus::from($validated['status']);

        $parent = User::query()->findOrFail($controlRequest->sender_id);

        if ($newStatus === ControlRequestStatus::Approved) {
            Connection::query()->firstOrCreate(
                [
                    'parent_id' => $parent->id,
                    'child_id' => $user->id,
                ],
                []
            );
        } else {
            Notification::query()->create([
                'id' => (string) Str::uuid(),
                'type' => 'control_request_refused',
                'title' => 'Запрос на контроль отклонён',
                'data' => [
                    'message' => $user->name.' отклонил(а) ваш запрос на контроль.',
                    'control_request_id' => $controlRequest->id,
                    'child_id' => $user->id,
                ],
                'notifiable_type' => User::class,
                'notifiable_id' => $parent->id,
                'receiver_id' => $parent->id,
            ]);
        }

        $controlRequest->update(['status' => $newStatus]);

        return response()->json([
            'request' => $controlRequest->fresh(),
        ]);
    }
}
