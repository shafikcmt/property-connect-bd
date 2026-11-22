<x-layouts.app title="{{ __('Create your account') }}" robots="noindex,follow">
    <div class="mx-auto max-w-md px-4 py-14 sm:py-20">
        <h1 class="font-display text-3xl font-semibold">{{ __('Create your account') }}</h1>
        <div class="mt-6"><x-ui.flash /></div>
        <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
            @csrf
            <x-ui.input name="name" :label="__('Full name')" required autocomplete="name" autofocus />
            <x-ui.input name="email" type="email" :label="__('Email')" required autocomplete="email" />
            <x-ui.input name="phone" type="tel" :label="__('Phone (optional)')" :hint="__('Used only to contact you about your inquiries.')" autocomplete="tel" />
            <x-ui.input name="password" type="password" :label="__('Password')" :hint="__('At least 10 characters with letters and numbers.')" required autocomplete="new-password" />
            <x-ui.input name="password_confirmation" type="password" :label="__('Confirm password')" required autocomplete="new-password" />
            <x-ui.button type="submit" class="w-full">{{ __('Register') }}</x-ui.button>
        </form>
        <p class="mt-6 text-sm text-warm-700">{{ __('Already registered?') }} <a class="underline" href="{{ route('login') }}">{{ __('Log in') }}</a></p>
    </div>
</x-layouts.app>
