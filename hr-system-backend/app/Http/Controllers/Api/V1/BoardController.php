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
        $companyId = $request->user()->company_id;
        $boards = $companyId
            ? $this->boardService->getByCompany($companyId)
            : $this->boardService->all();

        return BoardResource::collection($boards);
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $dto = BoardDTO::fromArray($request->validated());
        $board = $this->boardService->createWithDefaultColumns($dto);

        return (new BoardResource($board))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Board $board): BoardResource
    {
        $board = $this->boardService->getWithColumns($board->id);

        return new BoardResource($board);
    }

    public function update(UpdateBoardRequest $request, Board $board): BoardResource
    {
        $dto = BoardDTO::fromArray($request->validated());
        $result = $this->boardService->updateBoard($board->id, $dto);

        return new BoardResource($result);
    }

    public function destroy(Board $board): JsonResponse
    {
        $this->boardService->deleteBoard($board->id);

        return response()->json(['message' => 'Board deleted successfully.'], Response::HTTP_OK);
    }

    public function archive(Board $board): BoardResource
    {
        $result = $this->boardService->archive($board->id);

        return new BoardResource($result);
    }
}
