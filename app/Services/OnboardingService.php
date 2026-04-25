<?php

namespace App\Services;

use App\DTOs\OnboardingChecklistDTO;
use App\Enums\ChecklistStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OnboardingType;
use App\Events\OffboardingCompleted;
use App\Events\OnboardingCompleted;
use App\Exceptions\BusinessException;
use App\Models\Employee;
use App\Models\OnboardingChecklist;
use App\Models\OnboardingChecklistItem;
use App\Repositories\Interfaces\OnboardingChecklistRepositoryInterface;
use App\Repositories\Interfaces\OnboardingTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OnboardingService extends BaseService
{
    public function __construct(
        protected OnboardingChecklistRepositoryInterface $checklistRepository,
        protected OnboardingTemplateRepositoryInterface $templateRepository,
    ) {
        parent::__construct($checklistRepository);
    }

    public function createFromTemplate(int $employeeId, int $templateId): OnboardingChecklist
    {
        return DB::transaction(function () use ($employeeId, $templateId) {
            $template = $this->templateRepository->findOrFail($templateId);
            $template->load('items');

            $employee = Employee::findOrFail($employeeId);

            /** @var OnboardingChecklist $checklist */
            $checklist = $this->checklistRepository->create([
                'company_id' => $employee->company_id,
                'employee_id' => $employeeId,
                'template_id' => $templateId,
                'type' => $template->type->value,
                'status' => ChecklistStatus::Pending->value,
                'started_at' => now(),
                'created_by' => auth()->id(),
            ]);

            foreach ($template->items as $templateItem) {
                $checklist->items()->create([
                    'title' => $templateItem->title,
                    'description' => $templateItem->description,
                    'is_required' => $templateItem->is_required,
                    'is_completed' => false,
                    'sort_order' => $templateItem->sort_order,
                ]);
            }

            return $checklist->load('items');
        });
    }

    public function createChecklist(OnboardingChecklistDTO $dto): OnboardingChecklist
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
            $data['created_by'] = $data['created_by'] ?? auth()->id();
            $data['status'] = ChecklistStatus::Pending->value;
            $data['started_at'] = now();

            /** @var OnboardingChecklist $checklist */
            $checklist = $this->checklistRepository->create($data);

            if ($dto->templateId) {
                $template = $this->templateRepository->findOrFail($dto->templateId);
                $template->load('items');

                foreach ($template->items as $templateItem) {
                    $checklist->items()->create([
                        'title' => $templateItem->title,
                        'description' => $templateItem->description,
                        'is_required' => $templateItem->is_required,
                        'is_completed' => false,
                        'sort_order' => $templateItem->sort_order,
                    ]);
                }
            }

            return $checklist->load('items');
        });
    }

    public function completeItem(int $checklistItemId, ?string $notes = null): OnboardingChecklistItem
    {
        return DB::transaction(function () use ($checklistItemId, $notes) {
            $item = OnboardingChecklistItem::findOrFail($checklistItemId);

            $item->update([
                'is_completed' => true,
                'completed_by' => auth()->id(),
                'completed_at' => now(),
                'notes' => $notes ?? $item->notes,
            ]);

            $item = $item->fresh();

            $checklist = $item->checklist;

            // Update checklist status to InProgress if it was Pending
            if ($checklist->status === ChecklistStatus::Pending) {
                $checklist->update(['status' => ChecklistStatus::InProgress->value]);
            }

            // Check if all required items are completed
            $requiredItems = $checklist->items()->where('is_required', true)->count();
            $completedRequiredItems = $checklist->items()
                ->where('is_required', true)
                ->where('is_completed', true)
                ->count();

            if ($requiredItems > 0 && $requiredItems === $completedRequiredItems) {
                $checklist->update([
                    'status' => ChecklistStatus::Completed->value,
                    'completed_at' => now(),
                ]);

                $checklist = $checklist->fresh();
                $checklist->load('employee');

                if ($checklist->type === OnboardingType::Onboarding) {
                    event(new OnboardingCompleted($checklist));
                } else {
                    event(new OffboardingCompleted($checklist, $checklist->employee));
                }
            }

            return $item;
        });
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->checklistRepository->getByEmployee($employeeId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->checklistRepository->paginateWithFilters($filters, $perPage);
    }

    public function completeOffboarding(int $checklistId): OnboardingChecklist
    {
        return DB::transaction(function () use ($checklistId) {
            /** @var OnboardingChecklist $checklist */
            $checklist = $this->checklistRepository->findOrFail($checklistId);
            $checklist->load(['items', 'employee.user']);

            if ($checklist->type !== OnboardingType::Offboarding) {
                throw new BusinessException('This checklist is not an offboarding checklist.');
            }

            // Verify all required items are complete
            $incompleteRequired = $checklist->items()
                ->where('is_required', true)
                ->where('is_completed', false)
                ->count();

            if ($incompleteRequired > 0) {
                throw new BusinessException(
                    "Cannot complete offboarding: {$incompleteRequired} required item(s) are still incomplete."
                );
            }

            // Update checklist status
            $checklist->update([
                'status' => ChecklistStatus::Completed->value,
                'completed_at' => now(),
            ]);

            $employee = $checklist->employee;

            // Revoke all Sanctum tokens for the user
            if ($employee->user) {
                $employee->user->tokens()->delete();

                // Deactivate user account
                $employee->user->update(['is_active' => false]);
            }

            // Set employee status to inactive
            $employee->update(['status' => EmployeeStatus::Inactive->value]);

            $checklist = $checklist->fresh();
            $checklist->load('employee');

            event(new OffboardingCompleted($checklist, $checklist->employee));

            return $checklist;
        });
    }
}
