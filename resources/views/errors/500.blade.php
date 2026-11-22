<x-layouts.app title="{{ __('Something went wrong') }}" robots="noindex,nofollow">
    <div class="mx-auto max-w-xl px-4 py-24 text-center">
        <p class="text-sm font-semibold text-brand-600">500</p>
        <h1 class="mt-2 font-display text-4xl font-semibold">{{ __('Something went wrong') }}</h1>
        <p class="mt-4 text-warm-700">{{ __("We've been notified and are looking into it. Please try again shortly.") }}</p>
        <p class="mt-8"><x-ui.button href="{{ route('home') }}">{{ __('Back to home') }}</x-ui.button></p>
    </div>
</x-layouts.app>
