<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Num;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In Bangla, show ০–৯ in visible page text. Only text nodes are touched: tags and attributes, scripts, styles,
 * textareas, code blocks and HTML entities are left exactly as rendered, so URLs, form values and JSON-LD stay valid.
 */
class LocalizeDigits
{
    private const TAG = '~(<(?:[^>"\']|"[^"]*"|\'[^\']*\')*>)~';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->getLocale() !== 'bn' || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html') || $response->isRedirection()) {
            return $response;
        }
        $html = $response->getContent();
        if (! is_string($html) || $html === '') {
            return $response;
        }

        $response->setContent(self::convert($html));

        return $response;
    }

    public static function convert(string $html): string
    {
        $parts = preg_split(self::TAG, $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }

        $skipUntil = null;
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) { // a tag
                if ($skipUntil === null && preg_match('~^<(script|style|textarea|pre|code)\b~i', $part, $m)) {
                    $skipUntil = strtolower($m[1]);
                } elseif ($skipUntil !== null && stripos($part, '</'.$skipUntil) === 0) {
                    $skipUntil = null;
                }

                continue;
            }
            if ($skipUntil === null && $part !== '') {
                $parts[$i] = preg_replace_callback('~&#?[a-zA-Z0-9]+;|[0-9]+~', fn (array $m) => $m[0][0] === '&' ? $m[0] : Num::toBangla($m[0]), $part);
            }
        }

        return implode('', $parts);
    }
}
