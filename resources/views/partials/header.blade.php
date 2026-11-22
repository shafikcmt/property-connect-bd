@php
    $nav = [
        ['Home', route('home'), 'home'],
        ['Properties', '#', null], ['Projects', '#', null], ['Developers', '#', null], ['Locations', '#', null],
        ['Services', '#', null], ['About', '#', null], ['Blog', '#', null], ['Contact', '#', null],
    ];
@endphp
<header class="sticky top-0 z-40 border-b border-warm-100 bg-white/95 backdrop-blur" x-data="{ open: false }">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="whitespace-nowrap font-display text-xl font-semibold text-navy-900">Property Connect <span class="text-brand-600">BD</span></a>

        <nav aria-label="{{ __('Primary') }}" class="hidden xl:block">
            <ul class="flex items-center gap-4 text-sm font-medium text-warm-700">
                @foreach ($nav as [$label, $url, $route])
                    <li><a href="{{ $url }}" @if ($route && request()->routeIs($route)) aria-current="page" @endif class="py-2 hover:text-navy-900 {{ $route && request()->routeIs($route) ? 'text-navy-900' : '' }}">{{ __($label) }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="hidden items-center gap-2 xl:flex">
            <x-ui.button variant="secondary" href="#">{{ __('Submit Property') }}</x-ui.button>
            @auth
                <x-ui.button variant="ghost" :href="route('account.index')">{{ __('My account') }}</x-ui.button>
            @else
                <x-ui.button variant="ghost" :href="route('login')">{{ __('Login') }}</x-ui.button>
                <x-ui.button :href="route('register')">{{ __('Register') }}</x-ui.button>
            @endauth
        </div>

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-input xl:hidden" @click="open = !open"
                :aria-expanded="open.toString()" aria-controls="mobile-menu" aria-label="{{ __('Menu') }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>

    <div id="mobile-menu" x-show="open" x-cloak class="border-t border-warm-100 bg-white xl:hidden" @keydown.escape.window="open = false">
        <nav aria-label="{{ __('Mobile') }}" class="mx-auto max-w-7xl px-4 py-4">
            <ul class="space-y-1 text-base font-medium text-warm-900">
                @foreach ($nav as [$label, $url])
                    <li><a href="{{ $url }}" class="block rounded-input px-3 py-3 hover:bg-warm-50">{{ __($label) }}</a></li>
                @endforeach
            </ul>
            <div class="mt-4 flex flex-col gap-2">
                <x-ui.button variant="secondary" href="#">{{ __('Submit Property') }}</x-ui.button>
                @auth
                    <x-ui.button :href="route('account.index')">{{ __('My account') }}</x-ui.button>
                @else
                    <x-ui.button :href="route('login')">{{ __('Login / Register') }}</x-ui.button>
                @endauth
            </div>
        </nav>
    </div>
</header>
