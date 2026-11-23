{{-- Co-branded header for a developer's project landing page. The platform stays visible: this is NOT the developer's own site. --}}
<header class="sticky top-0 z-50 border-b border-warm-100 bg-white/95 backdrop-blur-md" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="bg-navy-900 px-4 py-1.5 text-center text-xs text-white/90">
        {{ __('Marketed by') }} <a href="{{ route('home') }}" class="font-semibold underline underline-offset-2">{{ config('app.name') }}</a>
        <span aria-hidden="true">·</span> <a href="{{ route('projects.index') }}" class="underline underline-offset-2">{{ __('All projects') }}</a>
    </div>
    <div class="container-x flex h-16 items-center justify-between gap-4">
        <a href="#top" class="flex min-w-0 items-center gap-3" aria-label="{{ $brand->name }}">
            <span class="flex h-10 w-12 shrink-0 items-center justify-center overflow-hidden"><x-picture :media="$brand->logo" :alt="$brand->name" fit="object-contain" sizes="96px" eager /></span>
            <span class="truncate font-display text-base font-semibold text-navy-900 sm:text-lg">{{ $brand->name }}</span>
        </a>
        <nav aria-label="{{ __('This project') }}" class="hidden lg:block">
            <ul class="flex items-center gap-6">@foreach ($brandNav as [$label, $anchor])<li><a href="#{{ $anchor }}" class="py-2 text-sm font-medium text-warm-700 transition-colors hover:text-navy-900">{{ __($label) }}</a></li>@endforeach</ul>
        </nav>
        <div class="flex items-center gap-2">
            <a href="#inquiry" class="inline-flex min-h-11 items-center rounded-input bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('Enquire') }}</a>
            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full lg:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="brand-menu" aria-label="{{ __('Menu') }}"><x-ic name="menu" x-show="!open" /><x-ic name="x" x-show="open" x-cloak /></button>
        </div>
    </div>
    <div id="brand-menu" x-show="open" x-cloak class="border-t border-warm-100 bg-white lg:hidden">
        <nav aria-label="{{ __('This project') }}" class="container-x py-3"><ul class="grid grid-cols-2 gap-1">@foreach ($brandNav as [$label, $anchor])<li><a href="#{{ $anchor }}" @click="open = false" class="block rounded-input px-3 py-3 text-base font-medium hover:bg-warm-50">{{ __($label) }}</a></li>@endforeach</ul></nav>
    </div>
</header>
