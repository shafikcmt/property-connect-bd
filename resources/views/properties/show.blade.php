@php
    $s = app(\App\Services\SettingsService::class);
    $rent = $property->purpose === \App\Enums\Purpose::Rent;
    $gallery = $property->media->where('collection', 'gallery')->where('is_private', false)->values();
    $plans = $property->media->where('collection', 'floor_plans')->where('is_private', false);
    $price = \App\Support\Money::priceLabel($property->price ? (float) $property->price : null, $property->price_negotiable, $rent, $property->rent_period);
    $loc = $property->location?->fullName();
    $phone = $s->get('contact.phone'); $wa = \App\Support\Phone::whatsapp($s->get('contact.whatsapp'));
    $waText = rawurlencode("Hello, I'm interested in {$property->title} ({$property->public_code}): ".route('properties.show', $property->slug));
    $favs = app(\App\Services\Favorites::class)->ids(request(), 'property');
    $desc = \Illuminate\Support\Str::limit(strip_tags((string) $property->description), 155);
    $title = $property->seo_title ?: "{$property->title} — ".($rent ? 'For rent' : 'For sale').($property->location ? ' in '.$property->location->name : '');
    $schema = [[
        '@context' => 'https://schema.org', '@type' => 'RealEstateListing', 'name' => $property->title, 'description' => $desc, 'url' => route('properties.show', $property->slug),
        'datePosted' => $property->published_at?->toDateString(), 'image' => $gallery->take(5)->map(fn ($m) => $m->url(1600))->all(),
        'offers' => $property->price ? ['@type' => 'Offer', 'price' => (float) $property->price, 'priceCurrency' => 'BDT'] : null,
        'address' => $property->location ? ['@type' => 'PostalAddress', 'addressLocality' => $property->location->name, 'addressCountry' => 'BD'] : null,
        'floorSize' => $property->size_sqft ? ['@type' => 'QuantitativeValue', 'value' => (float) $property->size_sqft, 'unitText' => 'sqft'] : null,
        'numberOfRooms' => $property->bedrooms,
    ]];
    $schema[0] = array_filter($schema[0], fn ($v) => $v !== null && $v !== []);
    $crumbs = [['Home', route('home')], ['Properties', route('properties.index')]];
    if ($property->location) { $crumbs[] = [$property->location->name, route('locations.show', $property->location->path)]; }
    $crumbs[] = [$property->title, null];
    $specs = array_filter([
        ['Property type', $property->type?->name], ['Purpose', $rent ? 'For rent' : 'For sale'], ['Size', $property->size_sqft ? number_format((float) $property->size_sqft).' sqft' : null],
        ['Bedrooms', $property->bedrooms], ['Bathrooms', $property->bathrooms], ['Balconies', $property->balconies],
        ['Floor', $property->floor_no !== null ? $property->floor_no.($property->total_floors ? ' of '.$property->total_floors : '') : null],
        ['Facing', $property->facing?->label()], ['Parking', $property->parking_spaces], ['Furnishing', $property->furnishing?->label()],
        ['Status', $property->property_status->label()], ['Year built', $property->year_built], ['Handover', $property->handover_on?->format('M Y')],
    ], fn ($r) => $r[1] !== null && $r[1] !== '');
@endphp
<x-layouts.app :title="$title" :description="$desc" :image="$gallery->first()?->url(1600)" :schema="$schema" :breadcrumbs="$crumbs" og-type="article">
    <x-breadcrumbs :items="$crumbs" />
    <div class="container-x pt-5"><x-gallery :images="$gallery" :title="$property->title" /></div>

    <div class="container-x mt-8 grid gap-10 pb-28 lg:grid-cols-[1fr_390px] lg:pb-10">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                @if ($property->isFeaturedNow())<span class="chip bg-gold-500 text-navy-900"><x-ic name="star" class="h-3.5 w-3.5" />{{ __('Featured') }}</span>@endif
                @if ($property->is_verified)<span class="chip bg-brand-600 text-white" title="{{ __('Verified by our team') }}"><x-ic name="badge" class="h-3.5 w-3.5" />{{ __('Verified') }} {{ $property->verified_at?->format('M Y') }}</span>@endif
                <span class="chip bg-navy-50 text-navy-900">{{ $rent ? __('For rent') : __('For sale') }}</span><span class="chip bg-warm-100 text-warm-700">ID {{ $property->public_code }}</span>
            </div>
            <h1 class="mt-3 font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $property->title }}</h1>
            @if ($loc)<p class="mt-2 flex items-center gap-2 text-warm-700"><x-ic name="pin" class="h-5 w-5 text-brand-600" />{{ $loc }}@if ($property->address_line) · {{ $property->address_line }}@endif</p>@endif
            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div><p class="font-display text-4xl font-semibold text-navy-900">{{ $price }}</p>
                    <p class="mt-1 text-sm text-warm-700">@if ($property->pricePerSqft()){{ \App\Support\Money::full($property->pricePerSqft()) }} / sqft · @endif @if ($property->price_negotiable){{ __('Price negotiable') }} · @endif {{ __('Price updated') }} {{ ($property->price_updated_at ?? $property->published_at ?? $property->updated_at)->format('M j, Y') }}</p></div>
                <div class="flex items-center gap-2">
                    <x-fav-button :id="$property->id" :active="in_array($property->id, $favs)" class="!border !border-warm-300 !bg-white" />
                    <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full border border-warm-300 px-4 text-sm font-medium" x-data="compare({{ $property->id }}, {{ in_array($property->id, session('compare', [])) ? 'true' : 'false' }})"><input type="checkbox" class="h-4 w-4 accent-[#0e8f6e]" :checked="on" @change="toggle()"><x-ic name="compare" class="h-4 w-4" />{{ __('Compare') }}<span x-show="msg" x-cloak x-text="msg" class="sr-only"></span></label>
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-warm-300" aria-label="{{ __('Share') }}" x-data @click="navigator.share ? navigator.share({title: @js($property->title), url: location.href}) : navigator.clipboard?.writeText(location.href).then(() => $el.classList.add('pop'))"><x-ic name="share" class="h-4 w-4" /></button>
                </div>
            </div>

            <section class="mt-10" aria-labelledby="ov"><h2 id="ov" class="font-display text-2xl font-semibold">{{ __('Overview') }}</h2>
                <dl class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-warm-100 bg-warm-100 sm:grid-cols-3">
                    @foreach ($specs as [$k, $v])<div class="bg-white p-4"><dt class="text-xs uppercase tracking-wide text-warm-500">{{ __($k) }}</dt><dd class="mt-1 font-semibold text-navy-900">{{ $v }}</dd></div>@endforeach
                </dl></section>

            @if ($property->description)<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('About this property') }}</h2><div class="prose-pc mt-3 whitespace-pre-line">{{ $property->description }}</div></section>@endif

            @if ($property->amenities->isNotEmpty())<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Amenities') }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach ($property->amenities as $a)<li class="flex items-center gap-2.5 text-warm-700"><x-ic name="check" class="h-5 w-5 text-brand-600" />{{ $a->name }}</li>@endforeach</ul></section>@endif

            @if ($plans->isNotEmpty())<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Floor plans') }}</h2><div class="mt-4 grid gap-4 sm:grid-cols-2">@foreach ($plans as $pl)<a href="{{ $pl->url(1600) }}" target="_blank" rel="noopener" class="zoom-wrap block overflow-hidden rounded-card border border-warm-100 bg-white p-2"><x-picture :media="$pl" :alt="__('Floor plan')" fit="object-contain" sizes="50vw" /></a>@endforeach</div></section>@endif

            @if ($property->payment_plan)<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Payment plan') }}</h2><p class="prose-pc mt-3 whitespace-pre-line">{{ $property->payment_plan }}</p></section>@endif
            @if ($property->nearby)<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Nearby facilities') }}</h2><ul class="mt-4 space-y-2 text-warm-700">@foreach ($property->nearby as $n)<li class="flex gap-2.5"><x-ic name="pin" class="h-5 w-5 shrink-0 text-brand-600" />{{ $n }}</li>@endforeach</ul></section>@endif

            <section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Location') }}</h2>
                <p class="mt-2 text-warm-700">{{ $loc }}</p>
                @if ($property->latitude && $property->longitude)<p class="mt-3"><a class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:underline" rel="noopener" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $property->latitude }}&mlon={{ $property->longitude }}#map=16/{{ $property->latitude }}/{{ $property->longitude }}"><x-ic name="map" class="h-5 w-5" />{{ __('View on map') }}</a></p>@endif
            </section>

            @if ($property->video_url && preg_match('~^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/~', $property->video_url))<p class="mt-8"><a class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:underline" href="{{ $property->video_url }}" target="_blank" rel="noopener noreferrer"><x-ic name="camera" class="h-5 w-5" />{{ __('Watch video tour') }}</a></p>@endif

            @if ($property->developer || $property->project)
            <section class="mt-10 rounded-card border border-warm-100 bg-warm-50 p-6"><h2 class="font-display text-xl font-semibold">{{ __('Developer & project') }}</h2>
                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-2 text-warm-700">
                    @if ($property->developer)<p><span class="text-xs uppercase tracking-wide text-warm-500">{{ __('Developer') }}</span><br><a class="font-semibold text-navy-900 hover:underline" href="{{ route('developers.show', $property->developer->slug) }}">{{ $property->developer->name }}</a></p>@endif
                    @if ($property->project)<p><span class="text-xs uppercase tracking-wide text-warm-500">{{ __('Project') }}</span><br><a class="font-semibold text-navy-900 hover:underline" href="{{ route('projects.show', $property->project->slug) }}">{{ $property->project->name }}</a></p>@endif
                </div></section>@endif

            <details class="mt-8 rounded-input border border-warm-100 p-4 text-sm" @if (session('form') === 'report') open @endif><summary class="flex cursor-pointer items-center gap-2 font-medium text-warm-700"><x-ic name="flag" class="h-4 w-4" />{{ __('Report this listing') }}</summary>
                @if (session('form') === 'report')<p class="mt-3 text-brand-700" role="status">{{ session('status') }}</p>@else
                <form method="POST" action="{{ route('properties.report', $property->slug) }}" class="mt-4 grid gap-3 sm:max-w-md">@csrf<input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div><label class="label" for="r-reason">{{ __('Reason') }}</label><select id="r-reason" name="reason" class="select" required>@foreach (['sold_or_unavailable' => 'Sold or no longer available', 'wrong_price' => 'Incorrect price', 'wrong_details' => 'Incorrect details', 'fake_or_scam' => 'Looks fake or misleading', 'other' => 'Other'] as $k => $l)<option value="{{ $k }}">{{ __($l) }}</option>@endforeach</select></div>
                    <div><label class="label" for="r-details">{{ __('Details (optional)') }}</label><textarea id="r-details" name="details" rows="3" class="textarea" maxlength="1000"></textarea></div>
                    <x-ui.button type="submit" variant="secondary">{{ __('Submit report') }}</x-ui.button></form>@endif</details>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('Contact') }}">
            <div class="grid grid-cols-2 gap-3">
                @if ($phone)<a href="tel:{{ preg_replace('/[^+\d]/', '', $phone) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-input border border-navy-900 text-sm font-semibold text-navy-900 hover:bg-navy-50"><x-ic name="phone" class="h-5 w-5" />{{ __('Call') }}</a>@endif
                @if ($wa)<a href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-input bg-[#25D366] text-sm font-semibold text-navy-900 hover:brightness-95"><x-ic name="whatsapp" class="h-5 w-5" />WhatsApp</a>@endif
            </div>
            <x-inquiry-form :property="$property" title="Interested? Ask us" cta="Send inquiry" />
            <x-visit-form :property="$property" />
        </aside>
    </div>

    @if ($similar->isNotEmpty())<section class="bg-warm-50 py-16"><div class="container-x"><x-section-head title="Similar properties" /><div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($similar as $i => $p)<x-property-card :property="$p" :i="$i" />@endforeach</div></div></section>@endif

    <div class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-3 gap-2 border-t border-warm-100 bg-white/95 p-3 backdrop-blur lg:hidden">
        @if ($phone)<a href="tel:{{ preg_replace('/[^+\d]/', '', $phone) }}" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-input border border-navy-900 text-sm font-semibold"><x-ic name="phone" class="h-4 w-4" />{{ __('Call') }}</a>@else<a href="#visit" class="inline-flex min-h-11 items-center justify-center rounded-input border border-navy-900 text-sm font-semibold">{{ __('Visit') }}</a>@endif
        @if ($wa)<a href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-input bg-[#25D366] text-sm font-semibold text-navy-900"><x-ic name="whatsapp" class="h-4 w-4" />WhatsApp</a>@else<a href="#visit" class="inline-flex min-h-11 items-center justify-center rounded-input border border-navy-900 text-sm font-semibold">{{ __('Visit') }}</a>@endif
        <a href="#inquiry" class="inline-flex min-h-11 items-center justify-center rounded-input bg-brand-600 text-sm font-semibold text-white">{{ __('Inquire') }}</a>
    </div>
</x-layouts.app>
