<x-layouts.app title="{{ __('Reset password') }}" robots="noindex,follow">
    <div class="mx-auto max-w-md px-4 py-14 sm:py-20">
        <h1 class="font-display text-3xl font-semibold">{{ __('Reset password') }}</h1>
        <div class="mt-6"><x-ui.flash /></div>
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-ui.input name="email" type="email" :value="$email" :label="__('Email')" required autocomplete="email" />
            <x-ui.input name="password" type="password" :label="__('New password')" required autocomplete="new-password" />
            <x-ui.input name="password_confirmation" type="password" :label="__('Confirm password')" required autocomplete="new-password" />
            <x-ui.button type="submit" class="w-full">{{ __('Reset password') }}</x-ui.button>
        </form>
    </div>
</x-layouts.app>
