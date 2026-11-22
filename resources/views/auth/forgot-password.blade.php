<x-layouts.app title="{{ __('Forgot password') }}" robots="noindex,follow">
    <div class="mx-auto max-w-md px-4 py-14 sm:py-20">
        <h1 class="font-display text-3xl font-semibold">{{ __('Forgot password') }}</h1>
        <div class="mt-6"><x-ui.flash /></div>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <x-ui.input name="email" type="email" :label="__('Email')" required autocomplete="email" autofocus />
            <x-ui.button type="submit" class="w-full">{{ __('Email reset link') }}</x-ui.button>
        </form>
    </div>
</x-layouts.app>
