@php $s = app(\App\Services\SettingsService::class); @endphp
<footer class="mt-16 bg-navy-900 text-warm-100">
    <div class="container-x grid gap-8 py-12 md:grid-cols-3">
        <div>
            <p class="font-display text-lg font-semibold text-white">{{ $brand->tr('name') }}</p>
            @if ($brand->summary)<p class="mt-2 max-w-sm text-sm text-warm-300">{{ $brand->summary }}</p>@endif
            <a href="{{ route('developers.show', $brand->slug) }}" class="mt-3 inline-block text-sm font-semibold text-gold-500 hover:underline">{{ __('About the developer') }}</a>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-gold-500">{{ __('Marketed by') }}</p>
            <p class="mt-2 font-semibold text-white">{{ $s->get('general.site_name') }}</p>
            <p class="mt-1 text-sm text-warm-300">{{ __('An independent real-estate marketing platform. This page is not the developer’s official website; agreements are between you and the developer.') }}</p>
        </div>
        <nav aria-label="{{ __('Legal') }}"><ul class="space-y-2 text-sm">
            <li><a class="hover:text-white" href="{{ route('home') }}">{{ __('Browse all properties and projects') }}</a></li>
            <li><a class="hover:text-white" href="{{ route('legal', 'privacy-policy') }}">{{ __('Privacy policy') }}</a></li>
            <li><a class="hover:text-white" href="{{ route('legal', 'terms-and-conditions') }}">{{ __('Terms') }}</a></li>
            <li><a class="hover:text-white" href="{{ route('legal', 'disclaimer') }}">{{ __('Disclaimer') }}</a></li>
        </ul></nav>
    </div>
    <p class="border-t border-white/10 py-4 text-center text-xs text-warm-300">© {{ date('Y') }} {{ $s->get('general.site_name') }}</p>
</footer>
