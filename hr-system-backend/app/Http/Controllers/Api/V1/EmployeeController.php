<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\EmployeeDTO;
use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeStatusRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\Access\PermissionService;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Employee::class);

        $user = $request->user();

        $filters = $request->only([
            'company_id', 'status', 'department_id', 'designation_id',
            'employment_type', 'manager_id', 'search', 'sort_by', 'sort_dir',
        ]);

        // Always pin to the current tenant — never trust company_id from
        // the request payload for non-super-admin users.
        if ($user && $user->company_id) {
            $filters['company_id'] = $user->company_id;
        }

        // Inject the department-scoped filter computed from the user's
        // effective `employee.view` scope. The repository understands the
        // `__visible_department_ids` and `__self_user_id` private keys.
        $scope = $this->permissions->getScope($user, 'employee.view');

        if ($scope === 'self') {
            $filters['__self_user_id'] = $user->id;
        } else {
            $filters['__visible_department_ids'] = $this->permissions
                ->visibleDepartmentIds($user, 'employee.view');
        }

        $employees = $this->employeeService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return EmployeeResource::collection($employees);
    }

    /**
     * GET /api/v1/employees/next-id-number
     *
     * Peek-ahead helper: inspect the most recently-created employee in
     * the caller's company, parse its employee_id_number, and return
     * the next sequential value. Supports arbitrary prefixes — e.g.
     * "EMP-0042" → "EMP-0043", "ACME-001" → "ACME-002".
     *
     * Falls back to "EMP-0001" for brand new companies. The frontend
     * uses this to pre-fill the Add Employee modal's ID field; the
     * admin can always overwrite it.
     */
    public function nextIdNumber(Request $request): JsonResponse
    {
        $companyId = $request->user()?->company_id ?? auth()->user()?->company_id;
        if (! $companyId) {
            return response()->json(['next' => 'EMP-0001']);
        }

        $last = Employee::where('company_id', $companyId)
            ->whereNotNull('employee_id_number')
            ->orderByDesc('id')
            ->first();

        // Default for first-ever employee in a company.
        if (! $last || ! $last->employee_id_number) {
            return response()->json(['next' => 'EMP-0001']);
        }

        // Split into alpha prefix + numeric suffix. Pad the suffix
        // back to its original width so "EMP-0042" stays 4 digits.
        $number = $last->employee_id_number;
        if (preg_match('/^(.*?)(\d+)$/', $number, $m)) {
            $prefix = $m[1];
            $num    = (int) $m[2];
            $width  = strlen($m[2]);
            $next   = $prefix . str_pad((string) ($num + 1), $width, '0', STR_PAD_LEFT);

            return response()->json(['next' => $next]);
        }

        // No numeric tail — just append -001.
        return response()->json(['next' => $number . '-001']);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $dto = EmployeeDTO::fromArray($request->validated());
        $employee = $this->employeeService->createEmployee($dto);

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Employee $employee): EmployeeResource
    {
        $this->authorize('view', $employee);

        $employee->load(['department', 'designation', 'branch', 'manager', 'user', 'departments']);

        return new EmployeeResource($employee);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $this->authorize('update', $employee);

        $dto = EmployeeDTO::fromArray($request->validated());
        $result = $this->employeeService->updateEmployee($employee->id, $dto);

        return new EmployeeResource($result);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $this->employeeService->deleteEmployee($employee->id);

        return response()->json(['message' => 'Employee deleted successfully.'], Response::HTTP_OK);
    }

    public function updateStatus(UpdateEmployeeStatusRequest $request, Employee $employee): EmployeeResource
    {
        $this->authorize('updateStatus', $employee);

        $status = EmployeeStatus::from($request->validated('status'));
        $result = $this->employeeService->changeStatus($employee->id, $status);

        return new EmployeeResource($result);
    }
}
