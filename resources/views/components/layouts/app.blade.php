@props(['title' => null, 'description' => null, 'robots' => 'index,follow'])
@php
    $settings = app(\App\Services\SettingsService::class);
    $siteName = $settings->get('general.site_name');
    $pageTitle = $title ? $title.' | '.$siteName : $siteName.' — '.$settings->get('general.tagline');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description ?? $settings->get('seo.default_description', $settings->get('general.tagline')) }}">
    <meta name="robots" content="{{ app()->environment('production') ? $robots : 'noindex,nofollow' }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-input focus:bg-white focus:px-4 focus:py-2">{{ __('Skip to content') }}</a>
    @include('partials.header')
    <main id="main" class="flex-1">{{ $slot }}</main>
    @include('partials.footer')
</body>
</html>
