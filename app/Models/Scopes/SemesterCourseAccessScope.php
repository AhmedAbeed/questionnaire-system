<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Models\Scopes\BaseAccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SemesterCourseAccessScope extends BaseAccessScope
{
    protected function buildAccessRules($user, Model $model): array
    {
        $rules = [
            'role' => $this->getUserPrimaryRole($user),
            'user_id' => $user->id,
            'faculty_ids' => $this->getUserFacultyIds($user),
            'access_type' => 'deny',
        ];
        if ($user->hasRole(UserRole::ADMIN) || $user->hasRole(UserRole::QUALITY_MANAGER)) {
            $rules['access_type'] = 'full';
        } elseif ($this->hasAnyRole($user, [UserRole::FACULTY_DEAN])) {
            $rules['access_type'] = 'faculty_based';
        }
        return $rules;
    }

    protected function applyAccessRules(Builder $builder, array $accessRules, $user, Model $model): void
    {
        match ($accessRules['access_type']) {
            'full' => $this->grantFullAccess($builder),
            'faculty_based' => $this->applyFacultyBasedAccess($builder, $accessRules),
            default => $this->denyAllAccess($builder),
        };
    }

    private function applyFacultyBasedAccess(Builder $builder, array $rules): void
    {
        if (empty($rules['faculty_ids'])) {
            $this->denyAllAccess($builder);
            return;
        }
        $builder->whereHas('course', function ($query) use ($rules) {
            $query->whereIn('faculty_id', $rules['faculty_ids']);
        });
    }

    private function getUserPrimaryRole($user): ?UserRole
    {
        $roleHierarchy = [
            UserRole::ADMIN,
            UserRole::FACULTY_DEAN,
        ];
        foreach ($roleHierarchy as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }
        return null;
    }

    private function getUserFacultyIds($user): array
    {
        $facultyIds = [];
        if ($user->hasRole(UserRole::FACULTY_DEAN) && $user->faculty) {
            $facultyIds = [$user->faculty->id];
        }
        return $facultyIds;
    }

    public static function withoutAccessControl(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope(static::class);
    }
} 