<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Baseline security headers (docs/14 §2). CSP is introduced report-only in Phase 9. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()');

        if (config('pcbd.noindex')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // CSP for the public site only (the admin panel's Livewire/Filament runtime needs a looser policy).
        // Report-only first: violations show in the browser console without breaking pages. Alpine's standard
        // build needs 'unsafe-eval'. Analytics hosts are added here only once consent-gated tags exist (Phase 10).
        if (! $request->is('admin', 'admin/*', 'erp', 'erp/*', 'livewire*', 'livewire-*/*')) {
            $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', [
                "default-src 'self'", "script-src 'self' 'unsafe-eval'", "style-src 'self' 'unsafe-inline'", "img-src 'self' data:",
                "font-src 'self'", "connect-src 'self'", "frame-ancestors 'self'", "base-uri 'self'", "form-action 'self'", "object-src 'none'",
            ]));
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
