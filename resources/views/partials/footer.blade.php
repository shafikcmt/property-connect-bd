@php $s = app(\App\Services\SettingsService::class); @endphp
<footer class="mt-16 bg-navy-900 text-warm-100">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
        <div>
            <p class="font-display text-lg font-semibold text-white">{{ $s->get('general.site_name') }}</p>
            <p class="mt-3 text-sm text-warm-300">{{ $s->get('general.tagline') }}</p>
        </div>
        <nav aria-label="{{ __('Explore') }}">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gold-500">{{ __('Explore') }}</h2>
            <ul class="mt-3 space-y-2 text-sm"><li>{{ __('Properties') }}</li><li>{{ __('Projects') }}</li><li>{{ __('Developers') }}</li><li>{{ __('Locations') }}</li></ul>
        </nav>
        <nav aria-label="{{ __('Company') }}">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gold-500">{{ __('Company') }}</h2>
            <ul class="mt-3 space-y-2 text-sm"><li>{{ __('About') }}</li><li>{{ __('Blog') }}</li><li>{{ __('Contact') }}</li><li>{{ __('FAQ') }}</li></ul>
        </nav>
        @if ($s->get('contact.phone') || $s->get('contact.email') || $s->get('contact.address'))
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gold-500">{{ __('Contact') }}</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @if ($s->get('contact.phone'))<li><a class="hover:underline" href="tel:{{ preg_replace('/[^+\d]/', '', $s->get('contact.phone')) }}">{{ $s->get('contact.phone') }}</a></li>@endif
                @if ($s->get('contact.email'))<li><a class="hover:underline" href="mailto:{{ $s->get('contact.email') }}">{{ $s->get('contact.email') }}</a></li>@endif
                @if ($s->get('contact.address'))<li>{{ $s->get('contact.address') }}</li>@endif
            </ul>
        </div>
        @endif
    </div>
    <div class="border-t border-white/10 py-5 text-center text-xs text-warm-300">
        © {{ date('Y') }} {{ $s->get('general.site_name') }}. {{ __('All rights reserved.') }}
    </div>
</footer>
