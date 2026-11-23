@php
    $site = app(\App\Services\SettingsService::class)->get('general.site_name');
    $schema = [['@context' => 'https://schema.org', '@type' => 'RealEstateAgent', 'name' => $site, 'url' => url('/'), 'description' => app(\App\Services\SettingsService::class)->get('general.tagline')],
               ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $site, 'url' => url('/'), 'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']]];
@endphp
<x-layouts.app :schema="$schema" description="Find verified apartments, houses, plots and new projects in Bangladesh. Browse trusted developers, compare properties and request site visits.">
    {{-- HERO --}}
    <section class="relative isolate overflow-hidden bg-navy-900 text-white">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0" style="background: radial-gradient(65% 70% at 85% 10%, rgba(20,184,166,.32), transparent 62%), radial-gradient(55% 60% at 5% 100%, rgba(201,162,75,.20), transparent 62%), linear-gradient(180deg, #0b1f3a 0%, #0d2646 100%)"></div>
            <div class="hero-grid absolute inset-0"></div>
            <div class="orb absolute -right-32 top-4 h-[28rem] w-[28rem] rounded-full bg-teal-500/20 blur-3xl"></div>
            <div class="orb absolute -left-28 bottom-8 h-80 w-80 rounded-full bg-gold-500/10 blur-3xl" style="animation-delay:-6s"></div>
            <svg class="absolute inset-x-0 bottom-0 h-24 w-full text-white/[.045] sm:h-32" viewBox="0 0 1440 120" preserveAspectRatio="none" fill="currentColor"><path d="M0 120V78h54V52h38v26h46V34h52v44h40V60h60v18h48V28h56v50h44V64h70v14h52V40h48v38h60V56h58v22h46V30h54v48h50V62h66v16h44V44h52v34h58V58h48v20h60V36h50v42h56V66h62v12h40V48h54v30h58V60h44v18h60V40h52v38h46V120z"/></svg>
        </div>
        <div class="container-x relative grid items-center gap-12 pb-28 pt-12 lg:grid-cols-[1.05fr_.95fr] lg:pb-36 lg:pt-14">
            <div class="hero-in">
                <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/[.07] px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-gold-500 backdrop-blur" style="--i:0"><x-ic name="sparkles" class="h-4 w-4" />{{ __('Verified property marketplace') }}</p>
                <h1 class="font-display text-[2.6rem] font-semibold leading-[1.05] !text-white sm:text-6xl lg:text-[4rem]" style="--i:1">{{ __('Find a home you will love,') }} <span class="swoosh relative inline-block text-gold-500">{{ __('with confidence.') }}<svg class="absolute -bottom-2 left-0 h-3 w-full text-gold-500/70" viewBox="0 0 300 12" preserveAspectRatio="none" fill="none" aria-hidden="true"><path class="swoosh-path" d="M3 8c60-7 130-8 200-4 30 2 60 2 94-1" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg></span></h1>
                <p class="mt-7 max-w-xl text-lg leading-relaxed text-warm-100/90" style="--i:2">{{ __('Explore apartments, houses, plots and new developments from trusted developers — with transparent details and help at every step.') }}</p>
                <div class="mt-9 flex flex-wrap gap-3" style="--i:3">
                    <x-ui.button :href="route('properties.purpose', 'for-sale')" class="!px-7 !py-3.5 shadow-lg shadow-brand-600/30">{{ __('Browse properties') }}<x-ic name="arrow" class="h-4 w-4" /></x-ui.button>
                    <a href="{{ route('projects.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-input border border-white/30 bg-white/[.04] px-7 py-3.5 text-sm font-semibold text-white backdrop-blur transition hover:border-white/60 hover:bg-white/10">{{ __('New projects') }}</a>
                </div>
                <ul class="mt-10 flex flex-wrap gap-x-7 gap-y-3 border-t border-white/10 pt-6 text-sm text-warm-100/90" style="--i:4">
                    <li class="flex items-center gap-2"><x-ic name="badge" class="h-5 w-5 text-gold-500" />{{ __('Verified only with records') }}</li>
                    <li class="flex items-center gap-2"><x-ic name="layers" class="h-5 w-5 text-gold-500" />{{ __('Transparent pricing') }}</li>
                    <li class="flex items-center gap-2"><x-ic name="calendar" class="h-5 w-5 text-gold-500" />{{ __('Site-visit support') }}</li>
                </ul>
            </div>
            <div class="relative hidden lg:block">
                <div class="absolute -inset-4 rounded-[2.2rem] bg-gradient-to-br from-teal-500/25 via-transparent to-gold-500/25 blur-2xl" aria-hidden="true"></div>
                <div class="float-a relative rounded-[1.9rem] p-1.5 ring-1 ring-white/15 shadow-2xl shadow-black/40"><x-hero-art /></div>
                <div class="float-b glass absolute -left-8 top-12 flex items-center gap-3 rounded-2xl px-4 py-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-white"><x-ic name="badge" class="h-5 w-5" /></span><span class="text-sm font-semibold leading-tight">{{ __('Verified listings') }}<br><span class="text-xs font-normal text-warm-100/80">{{ __('Checked by our team') }}</span></span></div>
                <div class="float-a glass absolute -right-6 top-24 flex items-center gap-3 rounded-2xl px-4 py-3" style="animation-delay:-4s"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white"><x-ic name="compare" class="h-5 w-5" /></span><span class="text-sm font-semibold leading-tight">{{ __('Compare homes') }}<br><span class="text-xs font-normal text-warm-100/80">{{ __('Up to 4, side by side') }}</span></span></div>
                <div class="float-b glass absolute -bottom-5 left-16 flex items-center gap-3 rounded-2xl px-4 py-3" style="animation-delay:-2s"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-gold-500 text-navy-900"><x-ic name="calendar" class="h-5 w-5" /></span><span class="text-sm font-semibold leading-tight">{{ __('Site visits') }}<br><span class="text-xs font-normal text-warm-100/80">{{ __('Request in one minute') }}</span></span></div>
            </div>
        </div>
    </section>
    <div class="container-x relative z-10 -mt-20 lg:-mt-24"><x-search-bar :options="$options" :popular="$locations->take(4)" /></div>

    {{-- STATS (real counts only) --}}
    @if ($stats['properties'] + $stats['projects'] + $stats['developers'] > 0)
    <section class="container-x mt-14" aria-label="{{ __('Platform at a glance') }}">
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([['properties', 'Properties listed', 'home'], ['projects', 'Projects', 'building'], ['developers', 'Developers', 'handshake'], ['verified', 'Verified listings', 'badge']] as [$k, $label, $ic])
                @if ($stats[$k] > 0)
                <div class="reveal rounded-card border border-warm-100 bg-white p-5 text-center" x-data="counter({{ $stats[$k] }})">
                    <x-ic :name="$ic" class="mx-auto h-6 w-6 text-brand-600" /><dd class="mt-2 font-display text-3xl font-semibold text-navy-900" x-text="label">{{ \App\Support\Num::local((string) $stats[$k]) }}</dd><dt class="text-sm text-warm-700">{{ __($label) }}</dt>
                </div>@endif
            @endforeach
        </dl>
    </section>
    @endif

    {{-- FEATURED PROPERTIES --}}
    @if ($featured->isNotEmpty())
    <section class="section-y"><div class="container-x">
        <x-section-head eyebrow="Handpicked" title="Featured properties" sub="Standout homes and investments, reviewed by our team."><a href="{{ route('properties.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:gap-3 transition-all">{{ __('View all') }}<x-ic name="arrow" class="h-4 w-4" /></a></x-section-head>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($featured as $i => $p)<x-property-card :property="$p" :i="$i % 3" />@endforeach</div>
    </div></section>
    @endif

    {{-- CATEGORIES --}}
    @if ($types->isNotEmpty())
    <section class="bg-warm-50 py-16"><div class="container-x">
        <x-section-head eyebrow="Browse by type" title="Whatever you are looking for" align="center" />
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($types->take(6) as $i => $t)
            <li class="reveal" style="--i: {{ $i }}"><a href="{{ route('properties.index', ['type' => [$t->slug]]) }}" class="group flex h-full flex-col items-center rounded-card border border-warm-100 bg-white p-5 text-center lift">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-navy-50 text-navy-900 transition group-hover:bg-brand-600 group-hover:text-white"><x-ic :name="in_array($t->group->value, ['commercial','industrial']) ? 'building' : ($t->group->value === 'land' ? 'map' : 'home')" class="h-6 w-6" /></span>
                <span class="mt-3 text-sm font-semibold text-navy-900">{{ $t->displayName() }}</span><span class="text-xs text-warm-500">{{ $t->listings_count }} {{ __('listings') }}</span></a></li>
            @endforeach
        </ul>
    </div></section>
    @endif

    {{-- FEATURED PROJECTS --}}
    @if ($projects->isNotEmpty())
    <section class="section-y"><div class="container-x">
        <x-section-head eyebrow="New developments" title="Featured projects" sub="Premium residential projects from partner developers."><a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:gap-3 transition-all">{{ __('All projects') }}<x-ic name="arrow" class="h-4 w-4" /></a></x-section-head>
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">@foreach ($projects as $i => $p)<x-project-card :project="$p" :i="$i" />@endforeach</div>
    </div></section>
    @endif

    {{-- LOCATIONS --}}
    @if ($locations->isNotEmpty())
    <section class="bg-navy-900 py-16 text-white sm:py-24"><div class="container-x">
        <div class="reveal mb-10 max-w-2xl"><p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-gold-500">{{ __('Location discovery') }}</p><h2 class="font-display text-3xl font-semibold !text-white sm:text-4xl">{{ __('Explore popular neighbourhoods') }}</h2></div>
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($locations as $i => $l)
            <li class="reveal" style="--i: {{ $i % 4 }}"><a href="{{ route('locations.show', $l->path) }}" class="group relative block overflow-hidden rounded-card bg-gradient-to-br from-navy-700 to-[#14707a] p-6 transition hover:-translate-y-1">
                <x-ic name="pin" class="h-6 w-6 text-gold-500" /><p class="mt-10 font-display text-2xl font-semibold">{{ $l->displayName() }}</p><p class="text-sm text-warm-100/80">{{ $l->parent?->displayName() }} · {{ $l->listings_count }} {{ __('listings') }}</p>
                <x-ic name="arrow" class="absolute right-5 top-5 h-5 w-5 opacity-0 transition group-hover:opacity-100" /></a></li>
            @endforeach
        </ul>
        <p class="mt-8"><a href="{{ route('locations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gold-500 hover:gap-3 transition-all">{{ __('All locations') }}<x-ic name="arrow" class="h-4 w-4" /></a></p>
    </div></section>
    @endif

    {{-- READY & COMMERCIAL --}}
    @if ($ready->isNotEmpty() || $commercial->isNotEmpty())
    <section class="section-y" x-data="{ tab: '{{ $ready->isNotEmpty() ? 'ready' : 'commercial' }}' }"><div class="container-x">
        <x-section-head eyebrow="Move in or invest" title="Ready homes & commercial space">
            <div class="inline-flex rounded-full bg-warm-100 p-1" role="tablist">
                @if ($ready->isNotEmpty())<button role="tab" :aria-selected="(tab==='ready').toString()" @click="tab='ready'" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="tab==='ready' ? 'bg-navy-900 text-white' : 'text-warm-700'">{{ __('Ready homes') }}</button>@endif
                @if ($commercial->isNotEmpty())<button role="tab" :aria-selected="(tab==='commercial').toString()" @click="tab='commercial'" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="tab==='commercial' ? 'bg-navy-900 text-white' : 'text-warm-700'">{{ __('Commercial') }}</button>@endif
            </div>
        </x-section-head>
        <div x-show="tab==='ready'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($ready as $i => $p)<x-property-card :property="$p" :i="$i" />@endforeach</div>
        <div x-show="tab==='commercial'" x-cloak class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($commercial as $i => $p)<x-property-card :property="$p" :i="$i" />@endforeach</div>
    </div></section>
    @endif

    {{-- WHY + HOW --}}
    <section class="bg-warm-50 py-16 sm:py-24"><div class="container-x">
        <x-section-head eyebrow="Why Property Connect BD" title="A clearer, calmer way to find property" align="center" />
        <ul class="grid gap-6 md:grid-cols-3">
            @foreach ([['badge', 'Verification you can see', 'Verified badges appear only when our team has recorded how and when a listing or developer was checked.'], ['layers', 'Transparent information', 'Prices show whether they are negotiable and when they were last updated. No hidden fine print.'], ['handshake', 'Help at every step', 'From shortlisting to site visits, our team supports buyers, owners and developers.']] as $i => [$ic, $t, $d])
            <li class="reveal rounded-card bg-white p-7" style="--i: {{ $i }}"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600/10 text-brand-700"><x-ic :name="$ic" class="h-6 w-6" /></span><h3 class="mt-5 font-display text-xl font-semibold">{{ __($t) }}</h3><p class="mt-2 text-warm-700">{{ __($d) }}</p></li>
            @endforeach
        </ul>
        <div class="mt-20"><x-section-head eyebrow="How it works" title="From search to site visit in three steps" align="center" />
            <ol class="relative grid gap-6 md:grid-cols-3">
                @foreach ([['search', 'Search & shortlist', 'Filter by location, budget and type, then save favorites and compare up to four properties.'], ['calendar', 'Request a visit', 'Send an inquiry or pick a preferred time for a site visit — no account needed.'], ['key', 'Decide with confidence', 'Our team answers questions, shares verified details and supports you through the process.']] as $i => [$ic, $t, $d])
                <li class="reveal relative rounded-card border border-warm-100 bg-white p-7" style="--i: {{ $i }}"><span class="absolute -top-4 left-7 flex h-8 w-8 items-center justify-center rounded-full bg-navy-900 text-sm font-semibold text-white">{{ $i + 1 }}</span><x-ic :name="$ic" class="h-7 w-7 text-brand-600" /><h3 class="mt-4 font-display text-xl font-semibold">{{ __($t) }}</h3><p class="mt-2 text-warm-700">{{ __($d) }}</p></li>
                @endforeach
            </ol></div>
    </div></section>

    {{-- DEVELOPERS --}}
    @if ($developers->isNotEmpty())
    <section class="section-y"><div class="container-x">
        <x-section-head eyebrow="Partners" title="Featured developers"><a href="{{ route('developers.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:gap-3 transition-all">{{ __('All developers') }}<x-ic name="arrow" class="h-4 w-4" /></a></x-section-head>
        <div class="grid grid-cols-2 gap-5 lg:grid-cols-4">@foreach ($developers->take(8) as $i => $d)<x-developer-card :developer="$d" :i="$i % 4" />@endforeach</div>
    </div></section>
    @endif

    {{-- LATEST --}}
    @if ($latest->isNotEmpty())
    <section class="bg-warm-50 py-16 sm:py-24"><div class="container-x">
        <x-section-head eyebrow="Just listed" title="Latest listings"><a href="{{ route('properties.index', ['sort' => 'newest']) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:gap-3 transition-all">{{ __('See all new listings') }}<x-ic name="arrow" class="h-4 w-4" /></a></x-section-head>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($latest as $i => $p)<x-property-card :property="$p" :i="$i % 4" />@endforeach</div>
    </div></section>
    @endif

    {{-- INVESTMENT --}}
    <section class="section-y"><div class="container-x grid items-center gap-10 lg:grid-cols-2">
        <div class="reveal"><p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">{{ __('Investment insight') }}</p><h2 class="font-display text-3xl font-semibold sm:text-4xl">{{ __('Think long-term before you buy') }}</h2>
            <p class="mt-4 text-warm-700">{{ __('Location, developer track record, handover timelines and payment plans all shape the return on a property. We help you compare options side by side and ask the right questions before you commit.') }}</p>
            <ul class="mt-6 space-y-3 text-warm-700">@foreach (['Check the developer and the project approvals', 'Compare price per sqft across similar listings', 'Understand the payment plan and handover date', 'Visit the site before you decide'] as $b)<li class="flex gap-3"><x-ic name="check" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />{{ __($b) }}</li>@endforeach</ul>
            <p class="mt-8 flex flex-wrap gap-3"><x-ui.button :href="route('forms.assistance')">{{ __('Talk to an advisor') }}</x-ui.button><x-ui.button variant="secondary" :href="route('blog.index')">{{ __('Read our guides') }}</x-ui.button></p></div>
        <div class="reveal grid grid-cols-2 gap-4" style="--i:2">
            @foreach ([['trend', 'Compare', 'Price per sqft, size and amenities side by side.'], ['doc', 'Verify', 'Ask for documents and approvals before booking.'], ['calendar', 'Visit', 'See the property and surroundings in person.'], ['handshake', 'Negotiate', 'Know the market range before you make an offer.']] as [$ic, $t, $d])
            <div class="rounded-card border border-warm-100 bg-white p-5 lift"><x-ic :name="$ic" class="h-6 w-6 text-brand-600" /><p class="mt-3 font-semibold text-navy-900">{{ __($t) }}</p><p class="mt-1 text-sm text-warm-700">{{ __($d) }}</p></div>
            @endforeach
        </div>
    </div></section>

    {{-- BLOG --}}
    @if ($posts->isNotEmpty())
    <section class="bg-warm-50 py-16 sm:py-24"><div class="container-x">
        <x-section-head eyebrow="Real estate articles" title="Guides & insights"><a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:gap-3 transition-all">{{ __('All articles') }}<x-ic name="arrow" class="h-4 w-4" /></a></x-section-head>
        <div class="grid gap-6 md:grid-cols-3">@foreach ($posts as $i => $post)<x-blog-card :post="$post" :i="$i" />@endforeach</div>
    </div></section>
    @endif

    {{-- CTA BAND --}}
    <section class="section-y"><div class="container-x grid gap-5 md:grid-cols-3">
        @foreach ([['home', 'Looking to buy?', 'Tell us what you need and we will shortlist verified options.', 'forms.assistance', 'Get buying help'], ['key', 'Selling or renting out?', 'List your property or get a valuation from our team.', 'forms.sell', 'Sell or rent property'], ['handshake', 'Are you a developer?', 'Partner with us to market your projects and reach serious buyers.', 'contact', 'Partner with us']] as $i => [$ic, $t, $d, $r, $c])
        <div class="reveal flex flex-col rounded-card p-8 {{ $i === 1 ? 'bg-navy-900 text-white' : 'border border-warm-100 bg-white' }}" style="--i: {{ $i }}"><x-ic :name="$ic" class="h-8 w-8 {{ $i === 1 ? 'text-gold-500' : 'text-brand-600' }}" /><h3 class="mt-4 font-display text-2xl font-semibold {{ $i === 1 ? '!text-white' : '' }}">{{ __($t) }}</h3><p class="mt-2 flex-1 {{ $i === 1 ? 'text-warm-100/80' : 'text-warm-700' }}">{{ __($d) }}</p>
            <p class="mt-6"><x-ui.button :href="route($r)" :variant="$i === 1 ? 'primary' : 'secondary'">{{ __($c) }}<x-ic name="arrow" class="h-4 w-4" /></x-ui.button></p></div>
        @endforeach
    </div></section>
</x-layouts.app>
