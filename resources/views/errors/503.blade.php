<x-layouts.app title="{{ __('Back soon') }}" robots="noindex,nofollow">
    <div class="mx-auto max-w-xl px-4 py-24 text-center">
        <p class="text-sm font-semibold text-brand-600">503</p>
        <h1 class="mt-2 font-display text-4xl font-semibold">{{ __('Back soon') }}</h1>
        <p class="mt-4 text-warm-700">{{ __("We're performing maintenance and will be back shortly.") }}</p>
        <p class="mt-8"><x-ui.button href="{{ route('home') }}">{{ __('Back to home') }}</x-ui.button></p>
    </div>
</x-layouts.app>
