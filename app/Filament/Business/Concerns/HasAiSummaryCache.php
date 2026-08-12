<?php

namespace App\Filament\Business\Concerns;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Stancl's CacheTenancyBootstrapper wraps every Cache facade call in
 * ->tags(...) for automatic per-tenant isolation, but the app's default
 * 'database' cache store doesn't support tagging — any Cache::get/put/
 * remember() call while tenancy is active throws. Cache::store() is a real
 * method (not magic-dispatched), so calling it directly returns a plain,
 * unwrapped repository that skips the broken wrapper. Callers must bake the
 * tenant id into their own cache keys manually since automatic isolation is
 * bypassed here.
 */
trait HasAiSummaryCache
{
    protected function aiSummaryCache(): Repository
    {
        return Cache::store('file');
    }
}
