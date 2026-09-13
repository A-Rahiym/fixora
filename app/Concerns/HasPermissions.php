<?php

namespace App\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Role/permission checks for staff users.
 *
 * Built once here (Phase 1) and reused by every policy and the
 * `permission` API middleware from Phase 1 onward.
 */
trait HasPermissions
{
    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->relationLoaded('role') ? $this->role : $this->role()->with('permissions')->first();

        if ($role === null) {
            return false;
        }

        return $role->permissions->contains('name', $permission);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
