<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Role;

class PermissionStore
{
    /**
     * @var array<string, array<string>>
     */
    private array $permissionCache = [];

    public function clear(): void
    {
        $this->permissionCache = [];
    }

    public function has(Organization $organization, string $permission): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return $this->userHas($organization, $user, $permission);
    }

    public function userHas(Organization $organization, User $user, string $permission): bool
    {
        if (! isset($this->permissionCache[$user->getKey().'|'.$organization->getKey()])) {
            if (! $user->belongsToTeam($organization)) {
                return false;
            }

            $permissions = $this->getPermissionsByUser($organization, $user);
            $this->permissionCache[$user->getKey().'|'.$organization->getKey()] = $permissions;
        } else {
            $permissions = $this->permissionCache[$user->getKey().'|'.$organization->getKey()];
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * @return array<string>
     */
    private function getPermissionsByUser(Organization $organization, User $user): array
    {
        if (! $user->belongsToTeam($organization)) {
            return [];
        }

        $member = $organization->users
            ->where('id', $user->getKey())
            ->first()
            ?->membership;

        if ($member === null || ! $member instanceof Member) {
            return [];
        }

        $role = $member->role;
        /** @var Role|null $roleObj */
        $roleObj = Jetstream::findRole($role);
        $permissions = $roleObj->permissions ?? [];

        // Per-member: employee can manage tasks on projects they have access to
        $canManageTasks = $member->can_manage_tasks
            || ($role === \App\Enums\Role::Employee->value && $organization->employees_can_manage_tasks);
        if ($role === \App\Enums\Role::Employee->value && $canManageTasks) {
            $permissions = array_merge($permissions, [
                'tasks:create',
                'tasks:update',
                'tasks:delete',
            ]);
        }

        // Per-member: employee can manage (create/update/delete) projects they can see
        if ($role === \App\Enums\Role::Employee->value && $member->can_manage_projects) {
            $permissions = array_merge($permissions, [
                'projects:create',
                'projects:update',
                'projects:delete',
            ]);
        }

        return $permissions;
    }

    /**
     * @return array<string>
     */
    public function getPermissions(Organization $organization): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return [];
        }

        return $this->getPermissionsByUser($organization, $user);
    }
}
