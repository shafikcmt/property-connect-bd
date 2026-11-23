<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Picks the public-site language: the visitor's saved choice, else PCBD_DEFAULT_LOCALE. The admin panel always stays English. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'livewire*', 'livewire-*/*')) {
            app()->setLocale('en');
            Carbon::setLocale('en');

            return $next($request);
        }

        $allowed = array_keys((array) config('pcbd.locales'));
        // Cookie only: it is readable before the session starts, so error pages (404 etc.) follow the visitor's language too.
        $locale = $request->cookie('pcbd_locale');
        if (! is_string($locale) || ! in_array($locale, $allowed, true)) {
            $locale = (string) config('pcbd.default_locale', 'en');
        }
        if (! in_array($locale, $allowed, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
