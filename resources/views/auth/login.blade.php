<x-layouts.app title="{{ __('Log in') }}" robots="noindex,follow">
    <div class="mx-auto max-w-md px-4 py-14 sm:py-20">
        <h1 class="font-display text-3xl font-semibold">{{ __('Log in') }}</h1>
        <div class="mt-6"><x-ui.flash /></div>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <x-ui.input name="email" type="email" :label="__('Email')" required autocomplete="email" autofocus />
            <x-ui.input name="password" type="password" :label="__('Password')" required autocomplete="current-password" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="h-4 w-4"> {{ __('Remember me') }}</label>
            <x-ui.button type="submit" class="w-full">{{ __('Log in') }}</x-ui.button>
        </form>
        <p class="mt-6 text-sm text-warm-700"><a class="underline" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a> · <a class="underline" href="{{ route('register') }}">{{ __('Create an account') }}</a></p>
    </div>
</x-layouts.app>
