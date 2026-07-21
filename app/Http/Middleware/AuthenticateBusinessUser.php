<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards plain (non-panel) tenant routes with the same check
 * User::canAccessPanel() uses for the business panel — Filament's own
 * Authenticate middleware can't be reused here since it resolves the
 * "current panel" from panel-registered routes only, and would fall back
 * to the default (admin) panel for a route like this one.
 */
class AuthenticateBusinessUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || $user->tenant_id === null || $user->tenant_id !== tenant('id')) {
            return redirect()->guest(Filament::getPanel('business')->getLoginUrl());
        }

        return $next($request);
    }
}
