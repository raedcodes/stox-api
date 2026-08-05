<?php

namespace App\Filament\Business\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Gates a whole Resource's CRUD access to a fixed set of roles within the
 * current business. Business roles (Owner/Manager/Cashier/Storekeeper)
 * previously existed only as labels — nothing actually restricted access
 * by role. Override allowedRoles() per resource; these hooks bypass
 * Filament's default Policy-based authorization entirely.
 *
 * Both the can*() booleans below AND their get*AuthorizationResponse()
 * counterparts must be overridden. Filament uses can*()/abort_unless() to
 * gate direct page/route access, but header/row action BUTTONS (CreateAction,
 * EditAction, ...) check getDefaultActionAuthorizationResponse(), which calls
 * the get*AuthorizationResponse() methods instead — and those fall through to
 * Laravel's Policy system if left unoverridden. That distinction is invisible
 * until a model happens to have its own Policy (e.g. User has UserPolicy, built
 * for the separate super-admin panel's UserResource): the resource page loads
 * fine, but every action button silently disappears for anyone the Policy
 * doesn't allow, even though canCreate()/canEdit()/etc. say yes.
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

    public static function getViewAnyAuthorizationResponse(): Response
    {
        return static::canViewAny() ? Response::allow() : Response::deny();
    }

    public static function getCreateAuthorizationResponse(): Response
    {
        return static::canCreate() ? Response::allow() : Response::deny();
    }

    public static function getEditAuthorizationResponse(Model $record): Response
    {
        return static::canEdit($record) ? Response::allow() : Response::deny();
    }

    public static function getDeleteAuthorizationResponse(Model $record): Response
    {
        return static::canDelete($record) ? Response::allow() : Response::deny();
    }

    public static function getDeleteAnyAuthorizationResponse(): Response
    {
        return static::canDeleteAny() ? Response::allow() : Response::deny();
    }

    public static function getViewAuthorizationResponse(Model $record): Response
    {
        return static::canView($record) ? Response::allow() : Response::deny();
    }
}
