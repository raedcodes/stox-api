<?php

namespace App\Auth;

use App\Enums\UserRole;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

/**
 * Authenticates the 'business' guard against either of two models: tenant
 * Staff (Manager/Cashier/StoreKeeper — the common case) or, if no Staff
 * matches, the tenant's Owner in the central `users` table. A plain
 * Eloquent-model provider can only serve one model, hence this.
 *
 * `retrieveById()`/`retrieveByToken()` dispatch on a "staff:" prefix that
 * Staff::getAuthIdentifier() adds to its session identifier — without it, a
 * session id of e.g. "5" would be genuinely ambiguous between tenant Staff
 * #5 and central User #5, two unrelated people, since both tables
 * auto-increment independently.
 */
class TenantAwareUserProvider implements UserProvider
{
    public function __construct(protected HasherContract $hasher) {}

    public function retrieveById($identifier)
    {
        if (is_string($identifier) && str_starts_with($identifier, 'staff:')) {
            return Staff::find(substr($identifier, strlen('staff:')));
        }

        return User::find($identifier);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $user = $this->retrieveById($identifier);

        if (! $user) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token)
    {
        $user->setRememberToken($token);

        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->save();
        $user->timestamps = $timestamps;
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return null;
        }

        $staffQuery = Staff::query();

        foreach ($credentials as $key => $value) {
            $staffQuery->where($key, $value);
        }

        if ($staff = $staffQuery->first()) {
            return $staff;
        }

        $ownerQuery = User::query()->where('tenant_id', tenant('id'));

        foreach ($credentials as $key => $value) {
            $ownerQuery->where($key, $value);
        }

        return $ownerQuery
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::Owner->value))
            ->first();
    }

    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials)
    {
        if (is_null($plain = $credentials['password'] ?? null)) {
            return false;
        }

        if (is_null($hashed = $user->getAuthPassword())) {
            return false;
        }

        return $this->hasher->check($plain, $hashed);
    }

    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make($credentials['password']),
        ])->save();
    }
}
