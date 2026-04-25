<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Self-lookup: resolve the employee backing this user so the
        // frontend can deep-link to /employees/{id} or /departments/{id}
        // without an extra round-trip. Only fired for the auth/me payload;
        // cached on the User instance so repeated serialisations don't
        // re-query.
        $employee = $this->resource instanceof \App\Models\User
            ? $this->resolveEmployee()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            // Legacy single-role enum column. Stays canonical for the
            // RoleMiddleware super-admin guard and policy `before()` shortcut.
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),

            // Spatie role names — the new fine-grained role system.
            // Resolved via getRoleNames() so it works without an explicit
            // ->load('roles') eager load (Spatie HasRoles caches internally).
            'roles' => $this->getRoleNames()->values(),

            // Flat list of permission names the user has via any role +
            // any direct grant. Frontend AuthContext converts this to a Set
            // for O(1) lookup via useAuth().can(name).
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),

            // Self-service shortcuts: the employee record backing this
            // user (if any) + their primary department id. Powers the
            // /me/profile and /me/department frontend redirects.
            'employee_id'   => $employee?->id,
            'department_id' => $employee?->department_id,

            'company_id' => $this->company_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveEmployee(): ?Employee
    {
        if (! property_exists($this->resource, 'cachedSelfEmployee')) {
            $this->resource->cachedSelfEmployee = Employee::where('user_id', $this->id)
                ->first();
        }

        return $this->resource->cachedSelfEmployee;
    }
}
