<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->only([
            'company_id',
            'auditable_type',
            'auditable_id',
            'user_id',
            'action',
            'start_date',
            'end_date',
        ]);

        // Scope to company for non-super admins
        if (! auth()->user()->isSuperAdmin()) {
            $filters['company_id'] = auth()->user()->company_id;
        }

        $auditLogs = $this->auditLogService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return AuditLogResource::collection($auditLogs);
    }

    public function show(AuditLog $auditLog): AuditLogResource
    {
        $this->authorize('view', $auditLog);

        $auditLog->load('user');

        return new AuditLogResource($auditLog);
    }

    public function getByModel(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        $request->validate([
            'model_type' => ['required', 'string'],
            'model_id' => ['required', 'integer'],
        ]);

        $auditLogs = $this->auditLogService->getByModel(
            $request->string('model_type'),
            $request->integer('model_id')
        );

        return AuditLogResource::collection($auditLogs);
    }
}
