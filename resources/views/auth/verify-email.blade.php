<x-layouts.app title="{{ __('Verify your email') }}" robots="noindex,follow">
    <div class="mx-auto max-w-md px-4 py-14 sm:py-20">
        <h1 class="font-display text-3xl font-semibold">{{ __('Verify your email') }}</h1>
        <div class="mt-6"><x-ui.flash /></div>
        <p class="text-warm-700">{{ __('We sent a verification link to your email address. Please click it to continue.') }}</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">@csrf<x-ui.button type="submit">{{ __('Resend verification email') }}</x-ui.button></form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<x-ui.button variant="ghost" type="submit">{{ __('Log out') }}</x-ui.button></form>
    </div>
</x-layouts.app>
