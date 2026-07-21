<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }
}
