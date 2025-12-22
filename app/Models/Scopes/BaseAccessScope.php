<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Services\AccessControl\AccessControlService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseAccessScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        try {
            // Allow full access for console (scheduler/queue) context
            if (app()->runningInConsole()) {
                $this->grantFullAccess($builder);
                return;
            }

            if ((auth()->check() && auth()->user()->hasRole('respondent'))) {
                $this->grantFullAccess($builder);
                return;
            }

            if (!$this->hasAuthenticatedUser()) {
                $this->denyAllAccess($builder);
                return;
            }

            $user = auth()->user();
            $accessRules = $this->buildAccessRules($user, $model);

            $this->applyAccessRules($builder, $accessRules, $user, $model);

        } catch (\Exception $e) {
            Log::error('Access scope error', [
                'model' => get_class($model),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            // Fail secure - deny access on error
            $this->denyAllAccess($builder);
        }
    }

    /**
     * Build access rules for the given user and model.
     * 
     * @param mixed $user
     * @param Model $model
     * @return array
     */
    abstract protected function buildAccessRules($user, Model $model): array;

    /**
     * Apply the built access rules to the query builder.
     */
    abstract protected function applyAccessRules(Builder $builder, array $accessRules, $user, Model $model): void;

    /**
     * Check if there's an authenticated user.
     */
    protected function hasAuthenticatedUser(): bool
    {
        return auth()->check() && auth()->user() !== null;
    }

    /**
     * Deny all access by adding an impossible condition.
     */
    protected function denyAllAccess(Builder $builder): void
    {
        $builder->whereRaw('1 = 0');
    }

    /**
     * Grant full access (no additional conditions).
     */
    protected function grantFullAccess(Builder $builder): void
    {
        // No additional conditions - allow all records
    }

    /**
     * Check if user has any of the specified roles.
     * @param mixed $user
     * @param UserRole[] $roles
     */
    protected function hasAnyRole($user, array $roles): bool
    {
        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        return collect($roles)->some(fn(UserRole $role) => $user->hasRole($role));
    }

    /**
     * Check if user has all of the specified roles.
     * @param mixed $user
     * @param UserRole[] $roles
     */
    protected function hasAllRoles($user, array $roles): bool
    {
        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        return collect($roles)->every(fn(UserRole $role) => $user->hasRole($role));
    }

    /**
     * Get user's role hierarchy level for comparison.
     * @param mixed $user
     * @return int
     */
    protected function getUserRoleLevel($user): int
    {
        if (!method_exists($user, 'hasRole')) {
            return 0;
        }

        return match (true) {
            $user->hasRole(UserRole::ADMIN) => UserRole::ADMIN->getLevel(),
            $user->hasRole(UserRole::FACULTY_DEAN) => UserRole::FACULTY_DEAN->getLevel(),
            $user->hasRole(UserRole::QUALITY_MANAGER) => UserRole::QUALITY_MANAGER->getLevel(),

            default => 0,
        };
    }

    /**
     * Apply relationship-based access control.
     */
    protected function applyRelationshipAccess(
        Builder $builder,
        string $relation,
        string $column,
        $value,
        string $operator = '='
    ): void {
        $builder->whereHas($relation, function ($query) use ($column, $value, $operator) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $operator, $value);
            }
        });
    }

    /**
     * Apply multiple relationship conditions with AND logic.
     */
    protected function applyMultipleRelationshipAccess(Builder $builder, array $conditions): void
    {
        foreach ($conditions as $condition) {
            $this->applyRelationshipAccess(
                $builder,
                $condition['relation'],
                $condition['column'],
                $condition['value'],
                $condition['operator'] ?? '='
            );
        }
    }
}