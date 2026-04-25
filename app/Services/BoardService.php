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

    /**
     * Return the boards visible to the given user in their own company,
     * honouring the board.view permission scope.
     *
     * Visibility rules:
     *   - Super Admin / company scope → every board in the company.
     *   - Dept Manager / Team Lead / Employee → boards whose `department_id`
     *     is in the user's visible-department set, PLUS boards with a
     *     NULL department_id (company-wide boards that are visible to
     *     everyone), PLUS boards where the user's employee record is an
     *     explicit member via the board_members pivot.
     *   - No board.view permission → empty collection.
     *
     * This is the canonical hook BoardController::index calls so that
     * Kanban drag-drop, columns, members, and attachments all keep working
     * unchanged — only the list of boards returned by /api/v1/boards is
     * restricted.
     */
    public function getVisibleForUser(\App\Models\User $user): Collection
    {
        $permissions = app(\App\Services\Access\PermissionService::class);

        if (! $permissions->can($user, 'board.view')) {
            return Board::whereRaw('1 = 0')->get();
        }

        $visibleDepartmentIds = $permissions->visibleDepartmentIds($user, 'board.view');
        $companyId = $user->company_id;

        $query = Board::where('company_id', $companyId)
            ->where('is_archived', false)
            ->with('department:id,name,name_ar,code')
            ->withCount(['tasks', 'members'])
            ->orderByDesc('created_at');

        if ($visibleDepartmentIds === null) {
            // Company scope: no filter.
            return $query->get();
        }

        // Limited scope: any board whose department_id is in the visible
        // set OR whose department_id is NULL OR where the user is an
        // explicit board member.
        $employeeId = $permissions->employeeIdForSelf($user);

        $query->where(function ($q) use ($visibleDepartmentIds, $employeeId) {
            $q->whereNull('department_id');

            if (! empty($visibleDepartmentIds)) {
                $q->orWhereIn('department_id', $visibleDepartmentIds);
            }

            if ($employeeId) {
                $q->orWhereHas('members', function ($m) use ($employeeId) {
                    $m->where('employees.id', $employeeId);
                });
            }
        });

        return $query->get();
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
