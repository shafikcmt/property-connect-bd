<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Privileged roles (Super Admin, Admin) must enrol in app-based 2FA before using the panel (ADR-0008).
 * Filament's built-in "required" flag is panel-wide and resolved at boot, so per-role enforcement lives here.
 * Until enrolled, the user may only reach their profile page (where MFA is set up) and log out.
 */
class EnforceAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'requiresMfa') || ! $user->requiresMfa()) {
            return $next($request);
        }

        if (MultiFactorChallenge::make()->hasEnabledProviders($user)) {
            return $next($request);
        }

        $profile = Filament::getProfileUrl();

        if ($request->url() === $profile || $request->routeIs('filament.admin.auth.*', 'livewire.*')) {
            return $next($request);
        }

        return redirect()->to($profile);
    }
}
