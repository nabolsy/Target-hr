<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DocumentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\EmployeeDocument;
use App\Services\Access\PermissionService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $filters = $request->only([
            'employee_id', 'type', 'status', 'search',
            'expiry_from', 'expiry_to', 'sort_by', 'sort_dir',
        ]);

        // Inject visible-employee filter from document.view scope.
        $filters['__visible_employee_ids'] = $this->permissions
            ->visibleEmployeeIds($request->user(), 'document.view');

        $documents = $this->documentService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeDocument::class);

        $dto = DocumentDTO::fromArray($request->validated());
        $document = $this->documentService->upload($dto, $request->file('file'));

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $document): DocumentResource
    {
        $result = $this->documentService->findOrFail($document);
        $this->authorize('view', $result);

        return new DocumentResource($result);
    }

    public function download(int $document)
    {
        $result = $this->documentService->findOrFail($document);
        $this->authorize('download', $result);

        // Two paths:
        //   1. The storage disk supports signed temporary URLs (e.g.
        //      S3) — return JSON {download_url, expires_in} so the FE
        //      can window.open() it directly. The signed URL carries
        //      its own auth so the browser never needs the bearer.
        //   2. Local disk → temporaryUrl throws RuntimeException.
        //      Stream the file inline through the API instead so the
        //      FE can download it as a blob. Avoids the previous bug
        //      where we returned the API route as the "download_url"
        //      and window.open() failed with "Route [login] not
        //      defined" because the redirect target needed auth.
        try {
            $url = \Illuminate\Support\Facades\Storage::disk('private')->temporaryUrl(
                $result->file_path,
                now()->addMinutes(15)
            );
            return response()->json([
                'download_url' => $url,
                'expires_in' => 900,
            ]);
        } catch (\RuntimeException) {
            // Stream binary inline. axios with responseType: 'blob'
            // (or fetch) handles this transparently.
            $disk = \Illuminate\Support\Facades\Storage::disk('private');
            if (! $disk->exists($result->file_path)) {
                return response()->json(['message' => 'File not found.'], 404);
            }
            return $disk->download(
                $result->file_path,
                $result->file_name ?: basename($result->file_path),
                ['Content-Type' => $result->mime_type ?: 'application/octet-stream']
            );
        }
    }

    public function destroy(int $document): JsonResponse
    {
        $result = $this->documentService->findOrFail($document);
        $this->authorize('delete', $result);

        $this->documentService->deleteDocument($document);

        return response()->json(['message' => 'Document deleted successfully.'], Response::HTTP_OK);
    }
}
