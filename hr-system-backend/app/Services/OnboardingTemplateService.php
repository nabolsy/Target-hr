<?php

namespace App\Services;

use App\DTOs\OnboardingTemplateDTO;
use App\Models\OnboardingTemplate;
use App\Models\OnboardingTemplateItem;
use App\Repositories\Interfaces\OnboardingTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OnboardingTemplateService extends BaseService
{
    public function __construct(
        protected OnboardingTemplateRepositoryInterface $templateRepository,
    ) {
        parent::__construct($templateRepository);
    }

    public function createTemplate(OnboardingTemplateDTO $dto): OnboardingTemplate
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;

            /** @var OnboardingTemplate $template */
            $template = $this->templateRepository->create($data);

            if ($dto->items) {
                foreach ($dto->items as $index => $item) {
                    $template->items()->create([
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'is_required' => $item['is_required'] ?? true,
                        'assigned_to_role' => $item['assigned_to_role'] ?? null,
                        'sort_order' => $item['sort_order'] ?? $index,
                    ]);
                }
            }

            return $template->load('items');
        });
    }

    public function updateTemplate(int $id, OnboardingTemplateDTO $dto): OnboardingTemplate
    {
        return DB::transaction(function () use ($id, $dto) {
            $data = $dto->toArray();

            /** @var OnboardingTemplate $template */
            $template = $this->templateRepository->update($id, $data);

            if ($dto->items !== null) {
                $template->items()->delete();

                foreach ($dto->items as $index => $item) {
                    $template->items()->create([
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'is_required' => $item['is_required'] ?? true,
                        'assigned_to_role' => $item['assigned_to_role'] ?? null,
                        'sort_order' => $item['sort_order'] ?? $index,
                    ]);
                }
            }

            return $template->load('items');
        });
    }

    public function addItem(int $templateId, array $itemData): OnboardingTemplateItem
    {
        $template = $this->templateRepository->findOrFail($templateId);

        $maxSortOrder = $template->items()->max('sort_order') ?? -1;

        return $template->items()->create([
            'title' => $itemData['title'],
            'description' => $itemData['description'] ?? null,
            'is_required' => $itemData['is_required'] ?? true,
            'assigned_to_role' => $itemData['assigned_to_role'] ?? null,
            'sort_order' => $itemData['sort_order'] ?? ($maxSortOrder + 1),
        ]);
    }

    public function removeItem(int $templateId, int $itemId): bool
    {
        $template = $this->templateRepository->findOrFail($templateId);

        return (bool) $template->items()->where('id', $itemId)->delete();
    }

    public function reorderItems(int $templateId, array $orderedItemIds): void
    {
        $this->templateRepository->findOrFail($templateId);

        foreach ($orderedItemIds as $sortOrder => $itemId) {
            OnboardingTemplateItem::where('id', $itemId)
                ->where('template_id', $templateId)
                ->update(['sort_order' => $sortOrder]);
        }
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->templateRepository->getByCompany($companyId);
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->templateRepository->getByDepartment($departmentId);
    }
}
