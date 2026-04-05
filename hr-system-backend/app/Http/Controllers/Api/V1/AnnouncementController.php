<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AnnouncementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcementService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $announcements = $this->announcementService->paginateWithFilters(
            $request->only([
                'company_id', 'department_id', 'type', 'is_pinned',
                'status', 'search', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $this->authorize('create', Announcement::class);

        $dto = AnnouncementDTO::fromArray($request->validated());
        $announcement = $this->announcementService->create($dto);

        return (new AnnouncementResource($announcement->load('reads')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $announcement): AnnouncementResource
    {
        $result = $this->announcementService->findOrFail($announcement);
        $this->authorize('view', $result);

        return new AnnouncementResource(
            $result->load(['creator', 'department', 'reads'])
        );
    }

    public function update(UpdateAnnouncementRequest $request, int $announcement): AnnouncementResource
    {
        $existing = $this->announcementService->findOrFail($announcement);
        $this->authorize('update', $existing);

        $dto = AnnouncementDTO::fromArray(array_merge(
            ['title' => $existing->title, 'body' => $existing->body, 'company_id' => $existing->company_id],
            $request->validated()
        ));
        $result = $this->announcementService->update($announcement, $dto);

        return new AnnouncementResource($result->load('reads'));
    }

    public function destroy(int $announcement): JsonResponse
    {
        $existing = $this->announcementService->findOrFail($announcement);
        $this->authorize('delete', $existing);

        $this->announcementService->delete($announcement);

        return response()->json(['message' => 'Announcement deleted successfully.'], Response::HTTP_OK);
    }

    public function publish(int $announcement): AnnouncementResource
    {
        $existing = $this->announcementService->findOrFail($announcement);
        $this->authorize('publish', $existing);

        $result = $this->announcementService->publish($announcement);

        return new AnnouncementResource($result->load('reads'));
    }

    public function markAsRead(int $announcement): JsonResponse
    {
        $this->announcementService->markAsRead($announcement, auth()->id());

        return response()->json(['message' => 'Announcement marked as read.'], Response::HTTP_OK);
    }

    public function acknowledge(int $announcement): JsonResponse
    {
        $this->announcementService->acknowledge($announcement, auth()->id());

        return response()->json(['message' => 'Announcement acknowledged.'], Response::HTTP_OK);
    }
}
