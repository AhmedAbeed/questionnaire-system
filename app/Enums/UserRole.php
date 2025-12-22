<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User Role Enumeration
 * 
 * Defines all available user roles in the system with their hierarchy levels.
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case FACULTY_DEAN = 'faculty_dean';
    case RESPONDENT = 'respondent';
    case QUALITY_MANAGER = 'quality_manager';

    /**
     * Get the hierarchy level of the role.
     */
    public function getLevel(): int
    {
        return match ($this) {
            self::ADMIN => 100,
            self::FACULTY_DEAN => 60,
            self::QUALITY_MANAGER => 40,
            self::RESPONDENT => 10,
        };
    }

    /**
     * Get human-readable label for the role.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::FACULTY_DEAN => 'Faculty Dean',
            self::QUALITY_MANAGER => 'Quality Manager',
            self::RESPONDENT => 'Respondent',
        };
    }

    /**
     * Get roles that can be managed by this role.
     */
    public function getManageableRoles(): array
    {
        return match ($this) {
            self::ADMIN => [
                self::FACULTY_DEAN,
                self::QUALITY_MANAGER,
                self::RESPONDENT,
            ],
            self::FACULTY_DEAN => [
                self::RESPONDENT,
            ],
            default => [],
        };
    }

    /**
     * Check if this role can manage another role.
     */
    public function canManage(self $role): bool
    {
        return in_array($role, $this->getManageableRoles(), true);
    }

    /**
     * Get all roles as array.
     */
    public static function getAllRoles(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get roles with their labels.
     */
    public static function getRolesWithLabels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($role) => [$role->value => $role->getLabel()])
            ->toArray();
    }

    public function roleNeedEntity(): ?string
    {
        return match ($this) {
            self::FACULTY_DEAN => 'faculty',
            default => null,
        };
    }
}