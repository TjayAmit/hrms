<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

class RoleAssignmentService
{
    /**
     * Assign a role to a user.
     */
    public function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->assignRole($role);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->removeRole($role);
    }

    /**
     * Sync multiple roles for a user.
     */
    public function syncRoles(User $user, array $roleNames): void
    {
        $roles = Role::whereIn('name', $roleNames)->get();
        $user->syncRoles($roles);
    }

    /**
     * Give a specific permission to a user.
     */
    public function givePermission(User $user, string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();
        $user->givePermissionTo($permission);
    }

    /**
     * Revoke a specific permission from a user.
     */
    public function revokePermission(User $user, string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();
        $user->revokePermissionTo($permission);
    }

    /**
     * Sync multiple permissions for a user.
     */
    public function syncPermissions(User $user, array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $user->syncPermissions($permissions);
    }

    /**
     * Get all available roles.
     */
    public function getAllRoles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    /**
     * Get all available permissions grouped by category.
     */
    public function getPermissionsByCategory(): array
    {
        $permissions = Permission::orderBy('name')->get();
        
        return $permissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->toArray();
    }

    /**
     * Check if a user has a specific permission.
     */
    public function userHasPermission(User $user, string $permissionName): bool
    {
        return $user->hasPermissionTo($permissionName);
    }

    /**
     * Check if a user has any of the specified roles.
     */
    public function userHasAnyRole(User $user, array $roleNames): bool
    {
        return $user->hasAnyRole($roleNames);
    }

    /**
     * Get users with a specific role.
     */
    public function getUsersWithRole(string $roleName): Collection
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        return $role->users;
    }

    /**
     * Create a new role with optional permissions.
     */
    public function createRole(string $roleName, array $permissions = []): Role
    {
        $role = Role::create(['name' => $roleName]);
        
        if (!empty($permissions)) {
            $role->givePermissionTo($permissions);
        }
        
        return $role;
    }

    /**
     * Delete a role and reassign its users to a fallback role.
     */
    public function deleteRole(string $roleName, ?string $fallbackRoleName = null): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        
        if ($fallbackRoleName) {
            $fallbackRole = Role::where('name', $fallbackRoleName)->firstOrFail();
            $role->users()->each(function ($user) use ($fallbackRole) {
                $user->assignRole($fallbackRole);
            });
        }
        
        $role->delete();
    }
}
