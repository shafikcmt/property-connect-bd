@props(['title' => null, 'description' => null, 'robots' => 'index,follow', 'image' => null, 'schema' => [], 'breadcrumbs' => [], 'canonical' => null, 'ogType' => 'website'])
@php
    $settings = app(\App\Services\SettingsService::class);
    $siteName = $settings->get('general.site_name');
    $pageTitle = $title ? $title.' | '.$siteName : $siteName.' — Verified properties & projects in Bangladesh';
    $desc = $description ?: $settings->get('seo.default_description', $settings->get('general.tagline'));
    $canon = $canonical ?: url()->current();
    $robotsFinal = app()->environment('production') && ! config('pcbd.noindex') ? $robots : 'noindex,nofollow';
    $ld = $schema;
    if ($breadcrumbs) {
        $ld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($breadcrumbs)->values()->map(fn ($b, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $b[0], 'item' => $b[1] ?? $canon])->all()];
    }
    $demo = (! app()->isProduction() || config('pcbd.allow_demo')) && cache()->remember('pcbd.demo-active', 60, fn () => \App\Models\Media::where('is_demo', true)->exists());
    $favCount = app(\App\Services\Favorites::class)->count(request());
    $cmpCount = count(session('compare', []));
    $wa = \App\Support\Phone::whatsapp($settings->get('contact.whatsapp'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $desc), 160) }}">
    <meta name="robots" content="{{ $robotsFinal }}">
    <link rel="canonical" href="{{ $canon }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $desc), 200) }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canon }}">
    @if ($image)<meta property="og:image" content="{{ $image }}"><meta name="twitter:card" content="summary_large_image">@endif
    <meta name="theme-color" content="#0B1F3A">
    {{-- Preload the two latin font files used above the fold so text does not reflow when they arrive. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('node_modules/@fontsource-variable/fraunces/files/fraunces-latin-wght-normal.woff2') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @foreach ($ld as $block)<script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>@endforeach
</head>
<body class="flex min-h-screen flex-col bg-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-input focus:bg-white focus:px-4 focus:py-2">{{ __('Skip to content') }}</a>
    @if ($demo)<div role="region" aria-label="{{ __('Demo notice') }}" class="bg-gold-500 px-4 py-1.5 text-center text-xs font-semibold text-navy-900">Demo content — illustrative listings, artwork and figures. Not real properties.</div>@endif
    @include('partials.header', ['favCount' => $favCount])
    <main id="main" class="flex-1">
        @if (session('status') && ! session('form'))<div class="container-x pt-6"><x-ui.flash /></div>@endif
        {{ $slot }}
    </main>
    @include('partials.footer')

    @if ($wa)
        <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hello, I found you on '.$siteName) }}" target="_blank" rel="noopener" aria-label="{{ __('Chat on WhatsApp') }}"
           class="fixed bottom-5 right-5 z-40 hidden h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-105 sm:flex"><x-ic name="whatsapp" class="h-7 w-7" /></a>
    @endif
    <div x-data="{ n: {{ $cmpCount }} }" @compare:count.window="n = $event.detail" x-show="n > 0" x-cloak x-transition
         class="fixed inset-x-0 bottom-4 z-40 mx-auto flex w-fit max-w-[92vw] items-center gap-3 rounded-full bg-navy-900 px-5 py-3 text-sm text-white shadow-xl">
        <x-ic name="compare" class="h-5 w-5 text-gold-500" /><span><span x-text="n"></span> {{ __('to compare') }}</span>
        <a href="{{ route('compare') }}" class="rounded-full bg-brand-600 px-4 py-1.5 font-semibold hover:bg-brand-700">{{ __('Compare') }}</a>
    </div>
</body>
</html>
