<?php

namespace App\Filament\Business\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Gates a whole Resource's CRUD access to a fixed set of roles within the
 * current business. Business roles (Owner/Manager/Cashier/Storekeeper)
 * previously existed only as labels — nothing actually restricted access
 * by role. Override allowedRoles() per resource; these hooks bypass
 * Filament's default Policy-based authorization entirely.
 */
trait RestrictedToRoles
{
    /**
     * @return array<\App\Enums\UserRole>
     */
    protected static function allowedRoles(): array
    {
        return [];
    }

    protected static function userHasAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(static::allowedRoles());
    }

    public static function canViewAny(): bool
    {
        return static::userHasAccess();
    }

    public static function canCreate(): bool
    {
        return static::userHasAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::userHasAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::userHasAccess();
    }

    public static function canDeleteAny(): bool
    {
        return static::userHasAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::userHasAccess();
    }
}
