<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\Currency;
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

    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'currency' => Currency::class,
        ];
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

    /**
     * Absolute URL to a path on this tenant's own subdomain, safe to call
     * with no active HTTP request (e.g. from a queued job) — unlike
     * building the host from request()->getHost(), which UserInfolist's
     * "Subdomain" link does for the request-bound equivalent of this.
     * Derives the base domain/scheme/port from config('app.url') instead.
     */
    public function url(string $path = ''): string
    {
        $appUrl = parse_url(config('app.url'));
        $scheme = $appUrl['scheme'] ?? 'http';
        $baseDomain = $appUrl['host'] ?? 'localhost';
        $port = $appUrl['port'] ?? null;
        $portSuffix = ($port && ! in_array($port, [80, 443], true)) ? ":{$port}" : '';

        return "{$scheme}://{$this->domain?->domain}.{$baseDomain}{$portSuffix}{$path}";
    }
}
