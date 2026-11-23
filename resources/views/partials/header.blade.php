@php
    $on = fn (string ...$r) => request()->routeIs(...$r);
    $link = fn ($active) => 'relative py-2 text-sm font-medium transition-colors hover:text-navy-900 '.($active ? 'text-navy-900' : 'text-warm-700');
@endphp
<header class="sticky top-0 z-50 border-b border-warm-100 bg-white/90 backdrop-blur-md" x-data="{ open: false, search: false, dd: null }" @keydown.escape.window="open = false; search = false; dd = null">
    <div class="container-x flex h-16 items-center justify-between gap-3 sm:gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 whitespace-nowrap" aria-label="{{ config('app.name') }}">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-navy-900 text-gold-500"><x-ic name="building" class="h-5 w-5" /></span>
            <span class="font-display text-[15px] font-semibold text-navy-900 sm:text-lg">Property Connect <span class="text-brand-600">BD</span></span>
        </a>

        <nav aria-label="{{ __('Primary') }}" class="hidden xl:block">
            <ul class="flex items-center gap-4">
                <li class="relative" @mouseenter="dd = 'p'" @mouseleave="dd = null">
                    <a href="{{ route('properties.index') }}" class="{{ $link($on('properties.*')) }} inline-flex items-center gap-1" @focus="dd = 'p'">{{ __('Properties') }}<x-ic name="chevron" class="h-4 w-4" /></a>
                    <div x-show="dd === 'p'" x-cloak class="menu-in absolute left-0 top-full w-56 pt-2"><div class="rounded-card border border-warm-100 bg-white p-2 shadow-xl">
                        @foreach ([['For sale', route('properties.purpose', 'for-sale')], ['For rent', route('properties.purpose', 'for-rent')], ['Commercial', route('properties.index', ['type' => ['commercial-property', 'office', 'shop']])], ['Land & plots', route('properties.index', ['type' => ['land-plot']])]] as [$l, $u])
                            <a href="{{ $u }}" class="block rounded-input px-3 py-2 text-sm text-warm-700 hover:bg-warm-50 hover:text-navy-900">{{ __($l) }}</a>
                        @endforeach
                    </div></div>
                </li>
                <li class="relative" @mouseenter="dd = 'j'" @mouseleave="dd = null">
                    <a href="{{ route('projects.index') }}" class="{{ $link($on('projects.*')) }} inline-flex items-center gap-1" @focus="dd = 'j'">{{ __('Projects') }}<x-ic name="chevron" class="h-4 w-4" /></a>
                    <div x-show="dd === 'j'" x-cloak class="menu-in absolute left-0 top-full w-52 pt-2"><div class="rounded-card border border-warm-100 bg-white p-2 shadow-xl">
                        @foreach ([['Upcoming', 'upcoming'], ['Ongoing', 'ongoing'], ['Ready to move', 'ready']] as [$l, $s])
                            <a href="{{ route('projects.status', $s) }}" class="block rounded-input px-3 py-2 text-sm text-warm-700 hover:bg-warm-50 hover:text-navy-900">{{ __($l) }}</a>
                        @endforeach
                    </div></div>
                </li>
                <li><a href="{{ route('developers.index') }}" class="{{ $link($on('developers.*')) }}">{{ __('Developers') }}</a></li>
                <li><a href="{{ route('locations.index') }}" class="{{ $link($on('locations.*')) }}">{{ __('Locations') }}</a></li>
                <li><a href="{{ route('services') }}" class="{{ $link($on('services')) }}">{{ __('Services') }}</a></li>
                <li><a href="{{ route('about') }}" class="{{ $link($on('about')) }}">{{ __('About') }}</a></li>
                <li><a href="{{ route('blog.index') }}" class="{{ $link($on('blog.*')) }}">{{ __('Blog') }}</a></li>
                <li><a href="{{ route('contact') }}" class="{{ $link($on('contact')) }}">{{ __('Contact') }}</a></li>
            </ul>
        </nav>

        <div class="flex items-center gap-1">
            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-navy-900 transition hover:bg-warm-100" @click="search = true; $nextTick(() => $refs.sq.focus())" aria-label="{{ __('Search') }}"><x-ic name="search" /></button>
            <a href="{{ route('favorites') }}" class="relative inline-flex h-11 w-11 items-center justify-center rounded-full text-navy-900 transition hover:bg-warm-100" aria-label="{{ __('Favorites') }}">
                <x-ic name="heart" />
                <span x-data="{ n: {{ $favCount }} }" @favorites:count.window="n = $event.detail" x-show="n > 0" x-cloak x-text="n" class="absolute right-0.5 top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-[11px] font-semibold text-white"></span>
            </a>
            @auth <a href="{{ route('account.index') }}" class="hidden h-11 w-11 items-center justify-center rounded-full text-navy-900 transition hover:bg-warm-100 xl:inline-flex" aria-label="{{ __('My account') }}"><x-ic name="user" /></a>
            @else <a href="{{ route('login') }}" class="hidden h-11 w-11 items-center justify-center rounded-full text-navy-900 transition hover:bg-warm-100 xl:inline-flex" aria-label="{{ __('Login / Register') }}" title="{{ __('Login / Register') }}"><x-ic name="user" /></a> @endauth
            <div class="hidden xl:block"><x-ui.button variant="primary" :href="route('forms.submit')" class="!px-4 !py-2">{{ __('Submit Property') }}</x-ui.button></div>
            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full xl:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-menu" aria-label="{{ __('Menu') }}">
                <x-ic name="menu" x-show="!open" /><x-ic name="x" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div id="mobile-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms class="border-t border-warm-100 bg-white xl:hidden">
        <nav aria-label="{{ __('Mobile') }}" class="container-x max-h-[78vh] overflow-y-auto py-4">
            <ul class="grid grid-cols-2 gap-1 text-base font-medium text-warm-900">
                @foreach ([['Home', route('home')], ['Properties', route('properties.index')], ['For sale', route('properties.purpose', 'for-sale')], ['For rent', route('properties.purpose', 'for-rent')], ['Projects', route('projects.index')], ['Developers', route('developers.index')], ['Locations', route('locations.index')], ['Services', route('services')], ['About', route('about')], ['Blog', route('blog.index')], ['Contact', route('contact')], ['FAQ', route('faq')]] as [$l, $u])
                    <li><a href="{{ $u }}" class="block rounded-input px-3 py-3 hover:bg-warm-50">{{ __($l) }}</a></li>
                @endforeach
            </ul>
            <div class="mt-4 grid gap-2">
                <x-ui.button variant="secondary" :href="route('forms.submit')">{{ __('Submit Property') }}</x-ui.button>
                @auth <x-ui.button :href="route('account.index')">{{ __('My account') }}</x-ui.button>
                @else <x-ui.button :href="route('login')">{{ __('Login / Register') }}</x-ui.button> @endauth
            </div>
        </nav>
    </div>

    {{-- Search overlay --}}
    <div x-show="search" x-cloak x-transition.opacity class="fixed inset-0 z-[60] bg-navy-900/70 p-4 backdrop-blur-sm" @click.self="search = false" role="dialog" aria-modal="true" aria-label="{{ __('Search') }}">
        <div class="mx-auto mt-16 max-w-2xl rounded-card bg-white p-4 shadow-2xl" x-data="suggest()" @click.outside="search = false">
            <form action="{{ route('search') }}" class="flex items-center gap-2">
                <x-ic name="search" class="ml-2 h-5 w-5 text-warm-500" />
                <input x-ref="sq" x-model="q" @input="input()" name="q" type="search" autocomplete="off" placeholder="{{ __('Search locations, projects, developers, properties…') }}" class="h-12 flex-1 border-0 bg-transparent text-base focus:outline-none focus:ring-0" aria-label="{{ __('Search') }}">
                <button class="rounded-input bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Search') }}</button>
            </form>
            <ul x-show="open && items.length" x-cloak class="mt-2 max-h-80 divide-y divide-warm-100 overflow-y-auto border-t border-warm-100">
                <template x-for="it in items" :key="it.url"><li><a :href="it.url" class="flex items-center justify-between gap-3 px-3 py-3 hover:bg-warm-50"><span x-text="it.label" class="font-medium text-navy-900"></span><span x-text="it.type" class="chip bg-navy-50 text-navy-700"></span></a></li></template>
            </ul>
            <p x-show="open && !items.length && !loading" x-cloak class="px-3 py-4 text-sm text-warm-700">{{ __('No quick matches — press Enter for full results.') }}</p>
        </div>
    </div>
</header>
