@php $items = [
 ['home', 'Buying assistance', 'Share your budget, preferred areas and timeline. We shortlist suitable properties and projects, arrange site visits and answer questions along the way.', 'forms.assistance', 'Get buying help'],
 ['key', 'Selling assistance', 'List your property with professional presentation and reach active buyers. Our team reviews every submission before it goes live.', 'forms.sell', 'Sell your property'],
 ['calendar', 'Rental assistance', 'Whether you want to rent out or find a rental, we help match owners and tenants and arrange viewings.', 'forms.rent', 'Rent out your property'],
 ['trend', 'Property valuation', 'Request an estimate based on the property, location and recent comparable listings.', 'forms.valuation', 'Request a valuation'],
 ['building', 'Developer marketing', 'Project promotion, property marketing, digital campaigns and buyer lead generation for developers.', 'contact', 'Discuss a partnership'],
 ['layers', 'Project promotion & landing pages', 'Dedicated project pages and campaign landing pages with inquiry tracking and campaign reporting.', 'contact', 'Talk to us'],
 ['handshake', 'Investment consultation', 'Understand locations, developers, handover timelines and payment plans before you invest.', 'forms.assistance', 'Book a consultation'],
 ['sparkles', 'Featured placements', 'Give a listing or project extra visibility with clearly labelled featured placements.', 'contact', 'Ask about featuring'],
]; @endphp
<x-layouts.app title="Real estate services" description="Buying, selling and rental assistance, property valuation, developer marketing and investment consultation." :breadcrumbs="[['Home', route('home')], ['Services', null]]">
    <x-page-hero eyebrow="Services" title="Support for buyers, owners and developers" sub="From finding the right property to marketing a new project — practical help at every stage." />
    <div class="container-x py-14"><ul class="grid gap-6 md:grid-cols-2">@foreach ($items as $i => [$ic, $t, $d, $r, $c])
        <li class="reveal flex gap-5 rounded-card border border-warm-100 bg-white p-7 lift" style="--i: {{ $i % 2 }}"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-700"><x-ic :name="$ic" class="h-6 w-6" /></span>
            <div><h2 class="font-display text-xl font-semibold">{{ __($t) }}</h2><p class="mt-2 text-warm-700">{{ __($d) }}</p><p class="mt-4"><a href="{{ route($r) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:gap-3 transition-all">{{ __($c) }}<x-ic name="arrow" class="h-4 w-4" /></a></p></div></li>@endforeach</ul></div>
</x-layouts.app>
