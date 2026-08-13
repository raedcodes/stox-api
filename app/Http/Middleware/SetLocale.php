<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the authenticated business-panel user's own language preference
 * (Staff or the central Owner User — both implement HasLocalePreference)
 * for this request. Falls back to config('app.locale') when unset or
 * unrecognized. Must run after Filament's Authenticate middleware so the
 * "business" guard is populated by the time this checks it.
 *
 * Scoped to authenticated requests only — the login screen itself stays in
 * the app's default locale, since there's no user yet to have a preference.
 */
class SetLocale
{
    protected const SUPPORTED_LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = auth('business')->user()?->preferredLocale();

        if ($locale && in_array($locale, self::SUPPORTED_LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
