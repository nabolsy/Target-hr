<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\BoardDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BoardController extends Controller
{
    public function __construct(private BoardService $boardService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // New department-aware path: returns only the boards the user can
        // see under their board.view scope. Kanban UI behaviour is
        // unchanged — the frontend just receives fewer rows for users
        // outside the company scope.
        $boards = $this->boardService->getVisibleForUser($request->user());

        return BoardResource::collection($boards);
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $this->authorize('create', Board::class);

        $dto = BoardDTO::fromArray($request->validated());
        $board = $this->boardService->createWithDefaultColumns($dto);

        return (new BoardResource($board))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Board $board): BoardResource
    {
        // Per-record access check. BoardPolicy::view already honours
        // department scoping for Department Manager / Employee roles via
        // its existing enum-based logic, so this is backwards compatible
        // with the current Kanban behaviour.
        $this->authorize('view', $board);

        $board = $this->boardService->getWithColumns($board->id);

        return new BoardResource($board);
    }

    public function update(UpdateBoardRequest $request, Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $dto = BoardDTO::fromArray($request->validated());
        $result = $this->boardService->updateBoard($board->id, $dto);

        return new BoardResource($result);
    }

    public function destroy(Board $board): JsonResponse
    {
        $this->authorize('delete', $board);

        $this->boardService->deleteBoard($board->id);

        return response()->json(['message' => 'Board deleted successfully.'], Response::HTTP_OK);
    }

    public function archive(Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $result = $this->boardService->archive($board->id);

        return new BoardResource($result);
    }
}
