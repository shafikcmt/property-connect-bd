<x-layouts.app title="{{ __('Page not found') }}" robots="noindex,nofollow">
    <div class="mx-auto max-w-xl px-4 py-24 text-center">
        <p class="text-sm font-semibold text-brand-600">404</p>
        <h1 class="mt-2 font-display text-4xl font-semibold">{{ __('Page not found') }}</h1>
        <p class="mt-4 text-warm-700">{{ __("We couldn't find the page you were looking for.") }}</p>
        <p class="mt-8"><x-ui.button href="{{ route('home') }}">{{ __('Back to home') }}</x-ui.button></p>
    </div>
</x-layouts.app>
