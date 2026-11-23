<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Remembers first-touch UTM parameters + referrer for the session so inquiries can be attributed. */
class CaptureUtm
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->session()->has('utm')) {
            $utm = collect($request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']))
                ->map(fn ($v) => is_string($v) ? mb_substr($v, 0, 100) : null)->filter()->all();
            $ref = $request->headers->get('referer');
            if ($utm || ($ref && ! str_contains($ref, $request->getHost()))) {
                $request->session()->put('utm', $utm + ['referrer' => $ref ? mb_substr($ref, 0, 500) : null, 'landing_url' => mb_substr($request->fullUrl(), 0, 500)]);
            }
        }

        return $next($request);
    }
}
