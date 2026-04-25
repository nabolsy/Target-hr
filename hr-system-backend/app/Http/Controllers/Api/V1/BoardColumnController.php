<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardColumnResource;
use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoardColumnController extends Controller
{
    public function index(Request $request, Board $board): AnonymousResourceCollection
    {
        // Columns are visible to anyone who can view the board.
        $this->authorize('view', $board);

        $includeArchived = $request->boolean('archived');

        $query = $board->columns()->getQuery();
        if ($includeArchived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        $columns = $query->orderBy('sort_order')->get();

        return BoardColumnResource::collection($columns);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $this->authorize('manageColumns', $board);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'wip_limit' => 'nullable|integer|min:0',
            'is_done_column' => 'nullable|boolean',
        ]);

        $nextOrder = ($board->columns()->max('sort_order') ?? -1) + 1;

        $column = $board->columns()->create([
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'wip_limit' => $data['wip_limit'] ?? null,
            'is_done_column' => $data['is_done_column'] ?? false,
            'sort_order' => $nextOrder,
        ]);

        return (new BoardColumnResource($column))->response()->setStatusCode(201);
    }

    public function update(Request $request, BoardColumn $column): BoardColumnResource
    {
        $this->authorize('manageColumns', $column->board);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color' => 'sometimes|nullable|string|max:20',
            'wip_limit' => 'sometimes|nullable|integer|min:0',
            'is_done_column' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $column->update($data);

        return new BoardColumnResource($column->fresh());
    }

    public function archive(BoardColumn $column): BoardColumnResource
    {
        $this->authorize('manageColumns', $column->board);

        $column->update(['archived_at' => now()]);

        return new BoardColumnResource($column->fresh());
    }

    public function restore(BoardColumn $column): BoardColumnResource
    {
        $this->authorize('manageColumns', $column->board);

        $column->update(['archived_at' => null]);

        return new BoardColumnResource($column->fresh());
    }

    public function destroy(BoardColumn $column): JsonResponse
    {
        $this->authorize('manageColumns', $column->board);

        if ($column->tasks()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a list that still has tasks. Move or archive its tasks first.',
            ], 422);
        }

        $column->delete();

        return response()->json(['message' => 'List deleted.']);
    }
}
