@php
    $s = app(\App\Services\SettingsService::class);
    $wa = \App\Support\Phone::whatsapp($s->get('contact.whatsapp'));
    $social = collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'])->filter(fn ($l, $k) => $s->get("social.$k"));
@endphp
<footer class="mt-24 bg-navy-900 text-warm-100">
    <div class="container-x grid gap-10 py-16 md:grid-cols-12">
        <div class="md:col-span-4">
            <p class="font-display text-xl font-semibold text-white">{{ $s->get('general.site_name') }}</p>
            <p class="mt-3 max-w-xs text-sm text-warm-300">{{ $s->get('general.tagline') }}</p>
            @if ($social->isNotEmpty())
                <ul class="mt-5 flex flex-wrap gap-3 text-sm">@foreach ($social as $k => $l)<li><a class="rounded-full border border-white/20 px-3 py-1 hover:bg-white/10" rel="noopener" target="_blank" href="{{ $s->get("social.$k") }}">{{ $l }}</a></li>@endforeach</ul>
            @endif
            <form method="POST" action="{{ route('newsletter.store') }}" class="mt-6 max-w-sm space-y-2">
                @csrf
                <label for="nl-email" class="text-sm font-medium text-white">{{ __('Property news & new listings') }}</label>
                <div class="flex gap-2"><input id="nl-email" type="email" name="email" required placeholder="you@example.com" class="h-11 min-w-0 flex-1 rounded-input border border-white/20 bg-white/10 px-3 text-sm text-white placeholder:text-warm-300 focus:border-gold-500 focus:outline-none">
                <button class="rounded-input bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Subscribe') }}</button></div>
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                <label class="flex items-start gap-2 text-xs text-warm-300"><input type="checkbox" name="consent" value="1" class="mt-0.5"> {{ __('I agree to receive emails and can unsubscribe at any time.') }}</label>
                @if (session('form') === 'newsletter')<p class="text-sm text-gold-500" role="status">{{ session('status') }}</p>@endif
                @error('email')<p class="text-sm text-gold-500">{{ $message }}</p>@enderror @error('consent')<p class="text-sm text-gold-500">{{ $message }}</p>@enderror
            </form>
        </div>
        <nav class="md:col-span-2" aria-label="{{ __('Explore') }}"><h2 class="text-xs font-semibold uppercase tracking-widest text-gold-500">{{ __('Explore') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm"><li><a class="hover:text-white" href="{{ route('properties.index') }}">{{ __('Properties') }}</a></li><li><a class="hover:text-white" href="{{ route('projects.index') }}">{{ __('Projects') }}</a></li><li><a class="hover:text-white" href="{{ route('developers.index') }}">{{ __('Developers') }}</a></li><li><a class="hover:text-white" href="{{ route('locations.index') }}">{{ __('Locations') }}</a></li><li><a class="hover:text-white" href="{{ route('compare') }}">{{ __('Compare') }}</a></li></ul></nav>
        <nav class="md:col-span-2" aria-label="{{ __('Services') }}"><h2 class="text-xs font-semibold uppercase tracking-widest text-gold-500">{{ __('Services') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm"><li><a class="hover:text-white" href="{{ route('forms.sell') }}">{{ __('Sell property') }}</a></li><li><a class="hover:text-white" href="{{ route('forms.rent') }}">{{ __('Rent out property') }}</a></li><li><a class="hover:text-white" href="{{ route('forms.valuation') }}">{{ __('Property valuation') }}</a></li><li><a class="hover:text-white" href="{{ route('forms.assistance') }}">{{ __('Buying assistance') }}</a></li><li><a class="hover:text-white" href="{{ route('services') }}">{{ __('All services') }}</a></li></ul></nav>
        <nav class="md:col-span-2" aria-label="{{ __('Company') }}"><h2 class="text-xs font-semibold uppercase tracking-widest text-gold-500">{{ __('Company') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm"><li><a class="hover:text-white" href="{{ route('about') }}">{{ __('About') }}</a></li><li><a class="hover:text-white" href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li><li><a class="hover:text-white" href="{{ route('faq') }}">{{ __('FAQ') }}</a></li><li><a class="hover:text-white" href="{{ route('contact') }}">{{ __('Contact') }}</a></li></ul></nav>
        <div class="md:col-span-2"><h2 class="text-xs font-semibold uppercase tracking-widest text-gold-500">{{ __('Contact') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @if ($s->get('contact.phone'))<li><a class="hover:text-white" href="tel:{{ preg_replace('/[^+\d]/', '', $s->get('contact.phone')) }}">{{ $s->get('contact.phone') }}</a></li>@endif
                @if ($wa)<li><a class="hover:text-white" rel="noopener" target="_blank" href="https://wa.me/{{ $wa }}">WhatsApp</a></li>@endif
                @if ($s->get('contact.email'))<li><a class="break-all hover:text-white" href="mailto:{{ $s->get('contact.email') }}">{{ $s->get('contact.email') }}</a></li>@endif
                @if ($s->get('contact.address'))<li class="text-warm-300">{{ $s->get('contact.address') }}</li>@endif
                @unless ($s->get('contact.phone') || $s->get('contact.email') || $wa)<li class="text-warm-300"><a class="hover:text-white" href="{{ route('contact') }}">{{ __('Send us a message') }}</a></li>@endunless
            </ul></div>
    </div>
    <div class="border-t border-white/10"><div class="container-x flex flex-col items-center justify-between gap-3 py-6 text-xs text-warm-300 sm:flex-row">
        <p>© {{ date('Y') }} {{ $s->get('general.site_name') }}. {{ __('All rights reserved.') }}</p>
        <ul class="flex flex-wrap gap-x-5 gap-y-1"><li><a class="hover:text-white" href="{{ route('legal', 'privacy-policy') }}">{{ __('Privacy Policy') }}</a></li><li><a class="hover:text-white" href="{{ route('legal', 'terms-and-conditions') }}">{{ __('Terms & Conditions') }}</a></li><li><a class="hover:text-white" href="{{ route('legal', 'cookie-policy') }}">{{ __('Cookie Policy') }}</a></li><li><a class="hover:text-white" href="{{ route('legal', 'disclaimer') }}">{{ __('Disclaimer') }}</a></li></ul>
    </div></div>
</footer>
