<?php

namespace Tests\Feature;

use App\Enums\BoardType;
use App\Enums\DepartmentStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\UserRole;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Services\Access\PermissionService;
use Database\Seeders\RoleAccessMatrixSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * End-to-end feature coverage for the role + department-scoped access
 * model introduced in Steps 1–9. Asserts:
 *
 *   - Company Admin gets company scope on every module
 *   - Department Manager sees only their managed subtree
 *   - Employee sees only self / own department
 *   - Kanban drag-drop works for employee assignees on their own tasks
 *     but is blocked on others' cards (Step 9 guarantee)
 *   - Confidential employee fields are hidden from out-of-scope viewers
 *
 * Fixtures are built inline (no reliance on domain factories beyond
 * User/Company) so the suite works on the SQLite in-memory config in
 * phpunit.xml without needing the full seed stack.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Department $parentDept;
    private Department $childDept;
    private Department $otherDept;
    private User $companyAdminUser;
    private User $hrManagerUser;
    private User $deptManagerUser;
    private User $employeeUser;
    private Employee $companyAdminEmployee;
    private Employee $parentDeptEmployee;  // in parentDept (managed by dept manager)
    private Employee $childDeptEmployee;   // in childDept (also managed via descendants)
    private Employee $otherDeptEmployee;   // in otherDept (outside dept manager's subtree)

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the role access matrix so Spatie roles + permissions exist.
        $this->seed(RoleAccessMatrixSeeder::class);

        // One tenant
        $this->company = Company::create([
            'name' => 'Test Co',
            'email' => 'test@example.com',
            'status' => 'active',
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        // Department hierarchy:
        //   parentDept (managed by deptManagerUser)
        //     └── childDept
        //   otherDept (separate subtree)
        $this->parentDept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Parent Dept',
            'status' => DepartmentStatus::Active->value,
        ]);
        $this->childDept = Department::create([
            'company_id' => $this->company->id,
            'parent_id' => $this->parentDept->id,
            'name' => 'Child Dept',
            'status' => DepartmentStatus::Active->value,
        ]);
        $this->otherDept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Other Dept',
            'status' => DepartmentStatus::Active->value,
        ]);

        // Users for each tier
        $this->companyAdminUser = $this->makeUser('admin@test.co', UserRole::CompanyAdmin, 'Company Admin');
        $this->hrManagerUser    = $this->makeUser('hr@test.co', UserRole::HrManager, 'HR Manager');
        $this->deptManagerUser  = $this->makeUser('mgr@test.co', UserRole::DepartmentManager, 'Department Manager');
        $this->employeeUser     = $this->makeUser('emp@test.co', UserRole::Employee, 'Employee');

        // Wire the dept manager onto the parent department so the
        // `managed_department` scope actually resolves to a subtree.
        $this->parentDept->update(['manager_id' => $this->deptManagerUser->id]);

        // Employees
        $this->companyAdminEmployee = $this->makeEmployee($this->companyAdminUser, $this->parentDept->id);
        $this->parentDeptEmployee   = $this->makeEmployee($this->deptManagerUser, $this->parentDept->id);
        $this->childDeptEmployee    = $this->makeEmployee(null, $this->childDept->id);

        // The employee user sits in childDept so their "own_department"
        // scope includes the child but NOT the other department.
        $this->makeEmployee($this->employeeUser, $this->childDept->id);

        // A colleague in a different department — Employee should NOT see this record.
        $this->otherDeptEmployee = $this->makeEmployee(null, $this->otherDept->id);
    }

    private function makeUser(string $email, UserRole $legacyRole, string $spatieRole): User
    {
        $user = User::create([
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('password'),
            'company_id' => $this->company->id,
            'role' => $legacyRole,
        ]);
        $user->assignRole($spatieRole);

        return $user;
    }

    private function makeEmployee(?User $user, int $departmentId, array $overrides = []): Employee
    {
        static $counter = 1000;
        $counter++;

        return Employee::create(array_merge([
            'company_id' => $this->company->id,
            'user_id' => $user?->id,
            'department_id' => $departmentId,
            'employee_id_number' => 'EMP-' . $counter,
            'first_name' => 'Test',
            'last_name' => 'User ' . $counter,
            'email' => "emp{$counter}@test.co",
            'employment_type' => EmploymentType::FullTime->value,
            'status' => EmployeeStatus::Active->value,
            'join_date' => now()->subYear(),
            'national_id' => 'NAT-' . $counter,
            'date_of_birth' => '1990-01-01',
            'address' => '123 Confidential St',
            'phone' => '+1555000' . $counter,
        ], $overrides));
    }

    // ─── Company Admin ───────────────────────────────────────────────

    public function test_company_admin_sees_all_employees(): void
    {
        $res = $this->actingAs($this->companyAdminUser)
            ->getJson('/api/v1/employees');

        $res->assertOk();
        // Should see at least the 4 employees we created (plus any factory
        // overflow — use >= to stay robust).
        $this->assertGreaterThanOrEqual(4, count($res->json('data')));
    }

    public function test_company_admin_sees_all_departments(): void
    {
        $res = $this->actingAs($this->companyAdminUser)
            ->getJson('/api/v1/departments');

        $res->assertOk();
        $names = collect($res->json('data'))->pluck('name')->all();
        $this->assertContains('Parent Dept', $names);
        $this->assertContains('Child Dept', $names);
        $this->assertContains('Other Dept', $names);
    }

    public function test_company_admin_has_company_scope(): void
    {
        $perms = app(PermissionService::class);

        $this->assertSame('company', $perms->getScope($this->companyAdminUser, 'employee.view'));
        $this->assertSame('company', $perms->getScope($this->companyAdminUser, 'board.manage'));
        $this->assertNull($perms->visibleDepartmentIds($this->companyAdminUser, 'employee.view'));
    }

    // ─── Department Manager ──────────────────────────────────────────

    public function test_department_manager_sees_only_managed_subtree(): void
    {
        $res = $this->actingAs($this->deptManagerUser)
            ->getJson('/api/v1/employees');

        $res->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();

        // Should include employees in parent + child depts
        $this->assertContains($this->parentDeptEmployee->id, $ids);
        $this->assertContains($this->childDeptEmployee->id, $ids);

        // Should NOT include the employee in the other department
        $this->assertNotContains($this->otherDeptEmployee->id, $ids);
    }

    public function test_department_manager_scope_resolves_to_subtree(): void
    {
        $perms = app(PermissionService::class);
        $scope = $perms->getScope($this->deptManagerUser, 'employee.view');
        $this->assertSame('managed_department', $scope);

        $visible = $perms->visibleDepartmentIds($this->deptManagerUser, 'employee.view');
        $this->assertContains($this->parentDept->id, $visible);
        $this->assertContains($this->childDept->id, $visible);
        $this->assertNotContains($this->otherDept->id, $visible);
    }

    public function test_department_manager_cannot_view_employee_outside_subtree(): void
    {
        $res = $this->actingAs($this->deptManagerUser)
            ->getJson("/api/v1/employees/{$this->otherDeptEmployee->id}");

        $res->assertForbidden();
    }

    public function test_department_manager_can_view_employee_inside_subtree(): void
    {
        $res = $this->actingAs($this->deptManagerUser)
            ->getJson("/api/v1/employees/{$this->childDeptEmployee->id}");

        $res->assertOk();
    }

    // ─── Employee self-service ───────────────────────────────────────

    public function test_employee_sees_only_own_department(): void
    {
        $res = $this->actingAs($this->employeeUser)
            ->getJson('/api/v1/employees');

        $res->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();

        // Employee is in childDept so they see childDept employees.
        $this->assertContains($this->childDeptEmployee->id, $ids);

        // But not out-of-subtree employees.
        $this->assertNotContains($this->otherDeptEmployee->id, $ids);
    }

    public function test_employee_cannot_delete_employees(): void
    {
        $res = $this->actingAs($this->employeeUser)
            ->deleteJson("/api/v1/employees/{$this->childDeptEmployee->id}");

        $res->assertForbidden();
    }

    public function test_employee_cannot_manage_departments(): void
    {
        $res = $this->actingAs($this->employeeUser)
            ->postJson('/api/v1/departments', [
                'company_id' => $this->company->id,
                'name' => 'Unauthorized Dept',
            ]);

        $res->assertForbidden();
    }

    public function test_employee_cannot_see_confidential_fields_of_colleagues(): void
    {
        $res = $this->actingAs($this->employeeUser)
            ->getJson("/api/v1/employees/{$this->childDeptEmployee->id}");

        $res->assertOk();
        $data = $res->json('data');

        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('email', $data);

        // Confidential fields are redacted via $this->when(...) so the
        // keys simply aren't present.
        $this->assertArrayNotHasKey('national_id', $data);
        $this->assertArrayNotHasKey('phone', $data);
        $this->assertArrayNotHasKey('date_of_birth', $data);
        $this->assertArrayNotHasKey('address', $data);
        $this->assertFalse($data['is_confidential_visible']);
    }

    public function test_self_sees_own_confidential_fields(): void
    {
        // The employee viewing their OWN record — user_id === viewer.id
        $selfRecord = Employee::where('user_id', $this->employeeUser->id)->first();

        $res = $this->actingAs($this->employeeUser)
            ->getJson("/api/v1/employees/{$selfRecord->id}");

        $res->assertOk();
        $data = $res->json('data');

        $this->assertTrue($data['is_confidential_visible']);
        $this->assertArrayHasKey('national_id', $data);
        $this->assertArrayHasKey('phone', $data);
    }

    // ─── Kanban access (Step 9) ──────────────────────────────────────

    public function test_employee_can_drag_their_own_task_even_without_task_move(): void
    {
        // Set up a board in the employee's own department with a task
        // and one alternate column. The employee is the assignee.
        $board = Board::create([
            'company_id' => $this->company->id,
            'name' => 'Test Board',
            'department_id' => $this->childDept->id,
            'type' => BoardType::Project->value,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);
        $todo = BoardColumn::create([
            'board_id' => $board->id, 'name' => 'Todo', 'sort_order' => 0, 'is_done_column' => false,
        ]);
        $done = BoardColumn::create([
            'board_id' => $board->id, 'name' => 'Done', 'sort_order' => 1, 'is_done_column' => true,
        ]);

        $task = Task::create([
            'company_id' => $this->company->id,
            'board_id' => $board->id,
            'column_id' => $todo->id,
            'title' => 'Do the thing',
            'sort_order' => 0,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);

        $employeeEmp = Employee::where('user_id', $this->employeeUser->id)->first();
        $task->assignees()->attach($employeeEmp->id, [
            'assigned_by' => $this->companyAdminUser->id,
            'assigned_at' => now(),
        ]);

        // Employee drags their own card from Todo → Done. Should work
        // even though they have no task.move grant in the matrix.
        $res = $this->actingAs($this->employeeUser)
            ->patchJson("/api/v1/tasks/{$task->id}/move", [
                'column_id' => $done->id,
                'sort_order' => 0,
            ]);

        $res->assertOk();
    }

    public function test_employee_cannot_drag_someone_elses_task(): void
    {
        $board = Board::create([
            'company_id' => $this->company->id,
            'name' => 'Test Board 2',
            'department_id' => $this->childDept->id,
            'type' => BoardType::Project->value,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);
        $todo = BoardColumn::create(['board_id' => $board->id, 'name' => 'Todo', 'sort_order' => 0, 'is_done_column' => false]);
        $done = BoardColumn::create(['board_id' => $board->id, 'name' => 'Done', 'sort_order' => 1, 'is_done_column' => true]);

        $task = Task::create([
            'company_id' => $this->company->id,
            'board_id' => $board->id,
            'column_id' => $todo->id,
            'title' => 'Not yours',
            'sort_order' => 0,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);
        // Assign to someone else (childDept colleague, not the viewer).
        $task->assignees()->attach($this->childDeptEmployee->id, [
            'assigned_by' => $this->companyAdminUser->id,
            'assigned_at' => now(),
        ]);

        $res = $this->actingAs($this->employeeUser)
            ->patchJson("/api/v1/tasks/{$task->id}/move", [
                'column_id' => $done->id,
                'sort_order' => 0,
            ]);

        $res->assertForbidden();
    }

    public function test_employee_cannot_manage_board_columns(): void
    {
        $board = Board::create([
            'company_id' => $this->company->id,
            'name' => 'Test Board 3',
            'department_id' => $this->childDept->id,
            'type' => BoardType::Project->value,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);

        $res = $this->actingAs($this->employeeUser)
            ->postJson("/api/v1/boards/{$board->id}/columns", [
                'name' => 'Unauthorized Column',
            ]);

        $res->assertForbidden();
    }

    public function test_department_manager_can_manage_boards_in_subtree(): void
    {
        $board = Board::create([
            'company_id' => $this->company->id,
            'name' => 'Mgr Board',
            'department_id' => $this->childDept->id,
            'type' => BoardType::Project->value,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);

        $res = $this->actingAs($this->deptManagerUser)
            ->postJson("/api/v1/boards/{$board->id}/columns", [
                'name' => 'Manager Column',
            ]);

        $res->assertCreated();
    }

    public function test_board_visibility_excludes_out_of_subtree_boards(): void
    {
        Board::create([
            'company_id' => $this->company->id,
            'name' => 'Mgr Subtree Board',
            'department_id' => $this->childDept->id,
            'type' => BoardType::Project->value,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);
        Board::create([
            'company_id' => $this->company->id,
            'name' => 'Out Of Subtree Board',
            'department_id' => $this->otherDept->id,
            'type' => BoardType::Project->value,
            'is_archived' => false,
            'created_by' => $this->companyAdminUser->id,
        ]);

        $res = $this->actingAs($this->deptManagerUser)
            ->getJson('/api/v1/boards');

        $res->assertOk();
        $names = collect($res->json('data'))->pluck('name')->all();

        $this->assertContains('Mgr Subtree Board', $names);
        $this->assertNotContains('Out Of Subtree Board', $names);
    }
}
