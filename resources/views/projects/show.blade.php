@php
    $s = app(\App\Services\SettingsService::class);
    $gallery = $project->media->where('collection', 'gallery')->where('is_private', false)->values();
    $plans = $project->media->where('collection', 'floor_plans')->where('is_private', false);
    $brochure = $project->media->where('collection', 'brochure')->where('is_private', false)->first();
    $range = $project->publicPriceRange();
    $wa = \App\Support\Phone::whatsapp($s->get('contact.whatsapp')); $phone = $s->get('contact.phone');
    $waText = rawurlencode("Hello, I'd like to know more about {$project->name}: ".route('projects.show', $project->slug));
    $desc = \Illuminate\Support\Str::limit(strip_tags((string) ($project->summary ?: $project->description)), 155);
    $crumbs = [['Home', route('home')], ['Projects', route('projects.index')], [$project->name, null]];
    $schema = [array_filter(['@context' => 'https://schema.org', '@type' => 'Residence', 'name' => $project->name, 'description' => $desc, 'url' => route('projects.show', $project->slug),
        'image' => $gallery->take(4)->map(fn ($m) => $m->url(1600))->all(), 'address' => $project->location ? ['@type' => 'PostalAddress', 'addressLocality' => $project->location->name, 'addressCountry' => 'BD'] : null])];
    $statusColor = ['upcoming' => 'bg-gold-500 text-navy-900', 'ongoing' => 'bg-info text-white', 'ready' => 'bg-brand-600 text-white', 'completed' => 'bg-warm-700 text-white'][$project->status->value];
    $favs = app(\App\Services\Favorites::class)->ids(request(), 'project');
    $branded = $project->isBranded();
    $brandNav = array_values(array_filter([
        $gallery->isNotEmpty() ? ['Gallery', 'gallery'] : null,
        $project->shareSummary() ? ['Shares', 'shares'] : null,
        $project->description ? ['Details', 'details'] : null,
        $project->payment_plan ? ['Payment plan', 'plan'] : ($project->installments->isNotEmpty() ? ['Payment plan', 'installments'] : null),
        $project->updates->isNotEmpty() ? ['Updates', 'updates'] : null,
        ['Contact', 'inquiry'],
    ]));
@endphp
<x-layouts.app :title="$project->seo_title ?: $project->name.' by '.$project->developer->name" :description="$project->seo_description ?: $desc" :image="$gallery->first()?->url(1600)" :schema="$schema" :breadcrumbs="$crumbs" :brand="$branded ? $project->developer : null" :brandNav="$brandNav">
    @if ($branded)
        <div id="top" class="border-b border-warm-100 bg-warm-50"><div class="container-x flex flex-wrap items-center justify-between gap-4 py-6">
            <div class="flex items-center gap-4"><span class="flex h-16 w-20 items-center justify-center overflow-hidden rounded-xl border border-warm-100 bg-white p-2"><x-picture :media="$project->developer->logo" :alt="$project->developer->name" fit="object-contain" sizes="160px" /></span>
                <p class="text-sm text-warm-700">{{ __('A project by') }}<br><a href="{{ route('developers.show', $project->developer->slug) }}" class="font-display text-lg font-semibold text-navy-900 hover:underline">{{ $project->developer->name }}</a></p></div>
            <p class="max-w-md text-xs text-warm-700">{{ __('Marketed by :site. This is not the developer’s official website.', ['site' => $s->get('general.site_name')]) }}</p></div></div>
    @else
        <x-breadcrumbs :items="$crumbs" />
    @endif
    <div id="gallery" class="container-x pt-5"><x-gallery :images="$gallery" :title="$project->name" /></div>
    <div class="container-x mt-8 grid gap-10 pb-28 lg:grid-cols-[1fr_390px] lg:pb-10">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2"><span class="chip {{ $statusColor }}">{{ $project->status->label() }}</span>
                @if ($project->isFeaturedNow())<span class="chip bg-gold-500 text-navy-900"><x-ic name="star" class="h-3.5 w-3.5" />{{ __('Featured') }}</span>@endif
                @if ($project->is_verified)<span class="chip bg-brand-600 text-white"><x-ic name="badge" class="h-3.5 w-3.5" />{{ __('Verified') }} {{ $project->verified_at?->format('M Y') }}</span>@endif</div>
            <div class="mt-3 flex items-start justify-between gap-4"><h1 class="font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $project->name }}</h1><x-fav-button kind="project" :id="$project->id" :active="in_array($project->id, $favs)" class="!border !border-warm-300 !bg-white shrink-0" /></div>
            <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-warm-700">@if ($project->location)<span class="flex items-center gap-2"><x-ic name="pin" class="h-5 w-5 text-brand-600" />{{ $project->location->fullName() }}</span>@endif
                <span>{{ __('by') }} <a class="font-semibold text-navy-900 hover:underline" href="{{ route('developers.show', $project->developer->slug) }}">{{ $project->developer->name }}</a></span></p>
            <div class="mt-5"><p class="text-xs text-warm-500">{{ $range ? __('Price range') : '' }}</p><p class="font-display text-3xl font-semibold text-navy-900">{{ $range ? \App\Support\Money::short($range['from']).($range['to'] ? ' – '.\App\Support\Money::short($range['to']) : '+') : __('Price on request') }}</p></div>

            <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-warm-100 bg-warm-100 sm:grid-cols-4">
                @foreach (array_filter([['Status', $project->status->label()], ['Handover', $project->handover_on?->format('M Y')], ['Total floors', $project->total_floors], ['Total units', $project->total_units], ['Available units', $availability ? $availability->available.' of '.$availability->total : null], ['Construction', $project->construction_progress !== null ? $project->construction_progress.'%' : null]], fn ($r) => $r[1] !== null) as [$k, $v])
                    <div class="bg-white p-4"><dt class="text-xs uppercase tracking-wide text-warm-500">{{ __($k) }}</dt><dd class="mt-1 font-semibold text-navy-900">{{ $v }}</dd></div>@endforeach
            </dl>
            @if ($project->construction_progress !== null && $project->status->value !== 'ready')<div class="mt-4" role="progressbar" aria-valuenow="{{ $project->construction_progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Construction progress') }}"><div class="h-2 overflow-hidden rounded-full bg-warm-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $project->construction_progress }}%"></div></div></div>@endif

            @if ($project->description)<section id="details" class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('About the project') }}</h2><div class="prose-pc mt-3 whitespace-pre-line">{{ $project->description }}</div></section>@endif

            @include('projects.partials.shares')

            @if ($project->unitTypes->isNotEmpty())<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Unit types') }}</h2>
                <div class="mt-4 overflow-x-auto rounded-card border border-warm-100" tabindex="0" role="region" aria-label="{{ __('Unit types table') }}"><table class="w-full min-w-[520px] text-left text-sm"><thead class="bg-warm-50 text-xs uppercase tracking-wide text-warm-500"><tr><th scope="col" class="p-3">{{ __('Type') }}</th><th scope="col" class="p-3">{{ __('Beds / baths') }}</th><th scope="col" class="p-3">{{ __('Size') }}</th><th scope="col" class="p-3">{{ __('Price') }}</th></tr></thead><tbody class="divide-y divide-warm-100">
                    @foreach ($project->unitTypes as $u)<tr><th scope="row" class="p-3 font-semibold text-navy-900">{{ $u->name }}</th><td class="p-3">{{ $u->bedrooms ?? '—' }} / {{ $u->bathrooms ?? '—' }}</td><td class="p-3">{{ $u->size_sqft_min ? number_format((float) $u->size_sqft_min).($u->size_sqft_max ? '–'.number_format((float) $u->size_sqft_max) : '').' sqft' : '—' }}</td><td class="p-3">{{ $project->price_verified && $u->price_from ? \App\Support\Money::short($u->price_from).($u->price_to ? ' – '.\App\Support\Money::short($u->price_to) : '') : __('On request') }}</td></tr>@endforeach
                </tbody></table></div></section>@endif

            @if ($project->amenities->isNotEmpty())<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Amenities') }}</h2><ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach ($project->amenities as $a)<li class="flex items-center gap-2.5 text-warm-700"><x-ic name="check" class="h-5 w-5 text-brand-600" />{{ $a->name }}</li>@endforeach</ul></section>@endif
            @if ($plans->isNotEmpty())<section class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Floor plans') }}</h2><div class="mt-4 grid gap-4 sm:grid-cols-2">@foreach ($plans as $pl)<a href="{{ $pl->url(1600) }}" target="_blank" rel="noopener" class="zoom-wrap block overflow-hidden rounded-card border border-warm-100 bg-white p-2"><x-picture :media="$pl" :alt="__('Floor plan')" fit="object-contain" sizes="50vw" /></a>@endforeach</div></section>@endif
            @if ($project->payment_plan)<section id="plan" class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Payment plan') }}</h2><p class="prose-pc mt-3 whitespace-pre-line">{{ $project->payment_plan }}</p></section>@endif
            @include('projects.partials.installments')
            @include('projects.partials.updates')
            @include('projects.partials.risk-note')
            <p class="mt-8 flex flex-wrap gap-4">@if ($brochure)<a class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:underline" href="{{ $brochure->url() }}" target="_blank" rel="noopener"><x-ic name="doc" class="h-5 w-5" />{{ __('Download brochure') }}</a>@endif
                @if ($project->video_url && preg_match('~^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/~', $project->video_url))<a class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:underline" href="{{ $project->video_url }}" target="_blank" rel="noopener noreferrer"><x-ic name="camera" class="h-5 w-5" />{{ __('Watch video') }}</a>@endif
                @if ($project->latitude && $project->longitude)<a class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:underline" target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat={{ $project->latitude }}&mlon={{ $project->longitude }}#map=16/{{ $project->latitude }}/{{ $project->longitude }}"><x-ic name="map" class="h-5 w-5" />{{ __('View on map') }}</a>@endif</p>
        </div>
        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
            <div class="grid grid-cols-2 gap-3">@if ($phone)<a href="tel:{{ preg_replace('/[^+\d]/', '', $phone) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-input border border-navy-900 text-sm font-semibold hover:bg-navy-50"><x-ic name="phone" class="h-5 w-5" />{{ __('Call') }}</a>@endif
                @if ($wa)<a href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-input bg-[#25D366] text-sm font-semibold text-navy-900"><x-ic name="whatsapp" class="h-5 w-5" />WhatsApp</a>@endif</div>
            <x-inquiry-form :project="$project" title="Request project details" cta="Send inquiry" />
            <x-visit-form :project="$project" />
        </aside>
    </div>
    @if ($properties->isNotEmpty())<section class="bg-warm-50 py-16"><div class="container-x"><x-section-head title="Available in this project" /><div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($properties as $i => $p)<x-property-card :property="$p" :i="$i" />@endforeach</div></div></section>@endif
    @if ($similar->isNotEmpty())<section class="py-16"><div class="container-x"><x-section-head title="Similar projects" /><div class="grid gap-6 md:grid-cols-3">@foreach ($similar as $i => $p)<x-project-card :project="$p" :i="$i" />@endforeach</div></div></section>@endif
    <div class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-2 gap-2 border-t border-warm-100 bg-white/95 p-3 backdrop-blur lg:hidden"><a href="#visit" class="inline-flex min-h-11 items-center justify-center rounded-input border border-navy-900 text-sm font-semibold">{{ __('Request visit') }}</a><a href="#inquiry" class="inline-flex min-h-11 items-center justify-center rounded-input bg-brand-600 text-sm font-semibold text-white">{{ __('Inquire') }}</a></div>
</x-layouts.app>
