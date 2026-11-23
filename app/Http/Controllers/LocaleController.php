<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, (array) config('pcbd.locales')), 404);

        // Return to the page the visitor was on, but never to another site.
        $previous = (string) $request->headers->get('referer');
        $back = $previous !== '' && parse_url($previous, PHP_URL_HOST) === $request->getHost() ? $previous : route('home');

        return redirect()->to($back)->withCookie(Cookie::make('pcbd_locale', $locale, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
