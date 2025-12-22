<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Models\Scopes\BaseAccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class CourseAccessScope extends BaseAccessScope
{
    /**
     * Build access rules based on user role and context.
     * @param mixed $user
     * @param Model $model
     * @return array
     */
    protected function buildAccessRules($user, Model $model): array
    {
        $rules = [
            'role' => $this->getUserPrimaryRole($user),
            'user_id' => $user->id,
            'faculty_ids' => $this->getUserFacultyIds($user),
            'access_type' => 'deny', // Default to deny
        ];

        // Determine access type based on role hierarchy
        if ($user->hasRole(UserRole::ADMIN) || $user->hasRole(UserRole::QUALITY_MANAGER)) {
            $rules['access_type'] = 'full';
        } elseif ($this->hasAnyRole($user, [UserRole::FACULTY_DEAN])) {
            $rules['access_type'] = 'faculty_based';
        }

        return $rules;
    }

    /**
     * Apply access rules to the query builder with optimized queries.
     */
    protected function applyAccessRules(Builder $builder, array $accessRules, $user, Model $model): void
    {
        match ($accessRules['access_type']) {
            'full' => $this->grantFullAccess($builder),
            'faculty_based' => $this->applyFacultyBasedAccess($builder, $accessRules),
            default => $this->denyAllAccess($builder),
        };
    }

    /**
     * Apply faculty-based access for deans.
     */
    private function applyFacultyBasedAccess(Builder $builder, array $rules): void
    {
        if (empty($rules['faculty_ids'])) {
            $this->denyAllAccess($builder);
            return;
        }

        // Ensure we are filtering the correct table (courses) by faculty_id
        $builder->whereIn('faculty_id', $rules['faculty_ids']);
    }



    /**
     * Get user's primary role for access control.
     * @param mixed $user
     * @return UserRole|null
     */
    private function getUserPrimaryRole($user): ?UserRole
    {
        $roleHierarchy = [
            UserRole::ADMIN,
            UserRole::QUALITY_MANAGER,
            UserRole::FACULTY_DEAN,
        ];

        foreach ($roleHierarchy as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Get faculty IDs associated with the user.
     */
    private function getUserFacultyIds($user): array
    {
        $facultyIds = [];
        if ($user->hasRole(UserRole::FACULTY_DEAN) && $user->faculty) {
            $facultyIds = [$user->faculty->id];
        }
        return $facultyIds;
    }

    /**
     * Scope for testing purposes - bypass access control.
     */
    public static function withoutAccessControl(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope(static::class);
    }
}