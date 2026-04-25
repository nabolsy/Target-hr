<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'company_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    // Alias Spatie's hasRole so we can keep our legacy enum-based override
    // without shadowing the trait method. Spatie's internals call
    // $this->hasRole(...) all over the place — if we shadow it without
    // aliasing, every call eats the enum cast and crashes.
    use HasRoles {
        hasRole as protected spatieHasRole;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Legacy-aware role check. Two call sites:
     *
     *   1. RoleMiddleware passes the enum value as a plain string
     *      (e.g. 'super_admin') — we resolve against the column enum.
     *   2. Spatie internally and any new code passes a role NAME, an array,
     *      a Collection, or a Role instance — we delegate to the real
     *      Spatie implementation.
     *
     * The decision rule: if the single argument is a string AND it parses
     * as a UserRole enum value, treat it as a legacy enum check. Otherwise
     * fall through to Spatie.
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($roles instanceof UserRole) {
            return $this->role === $roles;
        }

        if (is_string($roles) && ($enum = UserRole::tryFrom($roles)) !== null) {
            return $this->role === $enum;
        }

        return $this->spatieHasRole($roles, $guard);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }
}
