<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DocumentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\EmployeeDocument;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $documents = $this->documentService->paginateWithFilters(
            $request->only([
                'employee_id', 'type', 'status', 'search',
                'expiry_from', 'expiry_to', 'sort_by', 'sort_dir',
            ]),
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

    public function download(int $document): JsonResponse
    {
        $result = $this->documentService->findOrFail($document);
        $this->authorize('download', $result);

        $url = $this->documentService->download($document);

        return response()->json([
            'download_url' => $url,
            'expires_in' => 900, // 15 minutes in seconds
        ]);
    }

    public function destroy(int $document): JsonResponse
    {
        $result = $this->documentService->findOrFail($document);
        $this->authorize('delete', $result);

        $this->documentService->deleteDocument($document);

        return response()->json(['message' => 'Document deleted successfully.'], Response::HTTP_OK);
    }
}
