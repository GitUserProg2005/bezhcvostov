<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Connection;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $connectionPeers = [];
        if ($user && $user->role === UserRole::Student) {
            $connectionPeers = Connection::query()
                ->where('child_id', $user->id)
                ->with(['parent' => fn ($q) => $q->select('id', 'name', 'avatar')])
                ->get()
                ->map(fn (Connection $c) => $c->parent)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($peer) => [
                    'id' => $peer->id,
                    'name' => $peer->name,
                    'avatar_url' => $peer->avatar_url,
                    'role' => UserRole::Parent->value,
                ])
                ->all();
        } elseif ($user && $user->role === UserRole::Parent) {
            $connectionPeers = Connection::query()
                ->where('parent_id', $user->id)
                ->with(['child' => fn ($q) => $q->select('id', 'name', 'avatar')])
                ->get()
                ->map(fn (Connection $c) => $c->child)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($peer) => [
                    'id' => $peer->id,
                    'name' => $peer->name,
                    'avatar_url' => $peer->avatar_url,
                    'role' => UserRole::Student->value,
                ])
                ->all();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'account_code' => $user->account_code,
                    'role' => $user->role?->value,
                    'balance' => $user->balance,
                    'avatar_url' => $user->avatar_url,
                    'connection_peers' => $connectionPeers,
                ] : null,
            ],
        ];
    }
}
