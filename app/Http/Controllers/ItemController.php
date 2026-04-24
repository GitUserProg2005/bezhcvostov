<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class ItemController extends Controller
{
    public function getItems(): JsonResponse
    {
        $items = Item::query()
            ->with('slot:id,x,y,scale,type')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function getSlotItems(Request $request): JsonResponse
    {
        $items = $request->user()
            ->items()
            ->with('slot:id,x,y,scale,type')
            ->get();

        return response()->json([
            'success' => true,
            'items' => [
                'active' => $items->where('pivot.is_active', true)->values(),
                'inactive' => $items->where('pivot.is_active', false)->values(),
            ],
        ]);
    }

    public function addItemToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
        ]);

        $request->user()
            ->items()
            ->syncWithoutDetaching([
                $validated['item_id'] => ['is_active' => true],
            ]);

        return response()->json([
            'success' => true,
        ], 201);
    }

    public function setItemIsActive(Request $request) {
        $validated = $request->validate([
            'new_item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'old_item_id' => ['nullable', 'integer', Rule::exists('items', 'id')],
        ]);

        $user = $request->user();

        $user->items()->syncWithoutDetaching([
            $validated['new_item_id'] => ['is_active' => true],
            $validated['old_item_id'] => ['is_active' => false],
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
