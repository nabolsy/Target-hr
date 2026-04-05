<?php

namespace App\Services;

use App\DTOs\BoardDTO;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Repositories\Interfaces\BoardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BoardService extends BaseService
{
    public function __construct(
        protected BoardRepositoryInterface $boardRepository,
    ) {
        parent::__construct($boardRepository);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->boardRepository->getByCompany($companyId);
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->boardRepository->getByDepartment($departmentId);
    }

    public function getWithColumns(int $boardId): Model
    {
        return $this->boardRepository->getWithColumns($boardId);
    }

    public function createBoard(BoardDTO $dto): Board
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        return $this->boardRepository->create($data);
    }

    public function createWithDefaultColumns(BoardDTO $dto): Board
    {
        return DB::transaction(function () use ($dto) {
            $board = $this->createBoard($dto);

            $defaultColumns = [
                ['name' => 'To Do', 'sort_order' => 0, 'is_done_column' => false],
                ['name' => 'In Progress', 'sort_order' => 1, 'is_done_column' => false],
                ['name' => 'Review', 'sort_order' => 2, 'is_done_column' => false],
                ['name' => 'Done', 'sort_order' => 3, 'is_done_column' => true],
            ];

            foreach ($defaultColumns as $column) {
                BoardColumn::create([
                    'board_id' => $board->id,
                    'name' => $column['name'],
                    'sort_order' => $column['sort_order'],
                    'is_done_column' => $column['is_done_column'],
                ]);
            }

            return $board->load('columns');
        });
    }

    public function updateBoard(int $id, BoardDTO $dto): Model
    {
        return $this->boardRepository->update($id, $dto->toArray());
    }

    public function archive(int $boardId): Model
    {
        return $this->boardRepository->update($boardId, ['is_archived' => true]);
    }

    public function deleteBoard(int $id): bool
    {
        return $this->boardRepository->delete($id);
    }
}
