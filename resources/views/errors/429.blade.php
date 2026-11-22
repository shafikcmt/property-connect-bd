<x-layouts.app title="{{ __('Too many requests') }}" robots="noindex,nofollow">
    <div class="mx-auto max-w-xl px-4 py-24 text-center">
        <p class="text-sm font-semibold text-brand-600">429</p>
        <h1 class="mt-2 font-display text-4xl font-semibold">{{ __('Too many requests') }}</h1>
        <p class="mt-4 text-warm-700">{{ __("Please wait a moment and try again.") }}</p>
        <p class="mt-8"><x-ui.button href="{{ route('home') }}">{{ __('Back to home') }}</x-ui.button></p>
    </div>
</x-layouts.app>
