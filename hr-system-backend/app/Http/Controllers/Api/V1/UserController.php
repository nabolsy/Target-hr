<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles:id,name')
            ->where('company_id', $request->user()->company_id)
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        $users->getCollection()->transform(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'role' => $user->role?->value,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'employee_id' => 'nullable|integer|exists:employees,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company_id' => $request->user()->company_id,
            'role' => $this->mapSpatieRoleToEnum($data['role']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);

        if (! empty($data['employee_id'])) {
            $employee = Employee::find($data['employee_id']);
            if ($employee && ! $employee->user_id) {
                $employee->update(['user_id' => $user->id]);
            }
        }

        return response()->json([
            'data' => $user->load('roles:id,name'),
            'message' => 'User account created successfully.',
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'password' => 'sometimes|required|string|min:8',
            'role' => 'sometimes|required|string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        $updates = array_intersect_key($data, array_flip(['name', 'email', 'is_active']));

        if (isset($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $updates['role'] = $this->mapSpatieRoleToEnum($data['role']);
            $user->syncRoles([$data['role']]);
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        return response()->json([
            'data' => $user->fresh()->load('roles:id,name'),
            'message' => 'User updated.',
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->tokens()->delete();
        Employee::where('user_id', $user->id)->update(['user_id' => null]);
        $user->delete();

        return response()->json(['message' => 'User account removed.']);
    }

    /**
     * Map a Spatie role name (e.g. "Company Admin") to the legacy UserRole enum
     * so the users.role column stays in sync. Falls back to Employee for custom roles.
     */
    private function mapSpatieRoleToEnum(string $roleName): UserRole
    {
        return match ($roleName) {
            'Super Admin' => UserRole::SuperAdmin,
            'Company Admin' => UserRole::CompanyAdmin,
            'HR Manager' => UserRole::HrManager,
            'Department Manager' => UserRole::DepartmentManager,
            'Employee' => UserRole::Employee,
            default => UserRole::Employee,
        };
    }
}
