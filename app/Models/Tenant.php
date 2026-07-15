<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return array_merge(parent::getCustomColumns(), [
            'name',
        ]);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The tenant's primary subdomain. Tenants can have multiple domains via
     * domains(), but each is currently onboarded with exactly one.
     */
    public function domain(): HasOne
    {
        return $this->hasOne(Domain::class, 'tenant_id');
    }
}
