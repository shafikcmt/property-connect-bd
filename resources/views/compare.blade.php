@php
    $rows = [
        ['Price', fn ($p) => \App\Support\Money::priceLabel($p->price ? (float) $p->price : null, $p->price_negotiable, $p->purpose === \App\Enums\Purpose::Rent, $p->rent_period)],
        ['Price per sqft', fn ($p) => $p->pricePerSqft() ? \App\Support\Money::full($p->pricePerSqft()) : '—'],
        ['Size', fn ($p) => $p->size_sqft ? number_format((float) $p->size_sqft).' '.__('sqft') : '—'],
        ['Bedrooms', fn ($p) => $p->bedrooms ?? '—'], ['Bathrooms', fn ($p) => $p->bathrooms ?? '—'],
        ['Floor', fn ($p) => $p->floor_no ?? '—'], ['Parking', fn ($p) => $p->parking_spaces ?? '—'], ['Facing', fn ($p) => $p->facing?->label() ?? '—'],
        ['Location', fn ($p) => $p->location?->fullName() ?? '—'], ['Status', fn ($p) => $p->property_status->label()], ['Verified', fn ($p) => $p->is_verified ? 'Yes' : 'No'],
    ];
    $allAmenities = $properties->flatMap->amenities->unique('id')->sortBy('name');
@endphp
<x-layouts.app title="Compare properties" robots="noindex,follow" :breadcrumbs="[['Home', route('home')], ['Compare', null]]">
    <x-page-hero eyebrow="Compare" title="Compare properties" sub="Side-by-side comparison of up to four properties." />
    <div class="container-x py-12">
        @if ($properties->count() < 1)
            <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><x-ic name="compare" class="mx-auto h-10 w-10 text-warm-500" /><h2 class="mt-4 font-display text-2xl font-semibold">{{ __('No properties to compare') }}</h2><p class="mt-2 text-warm-700">{{ __('Tick “Compare” on up to four property cards to see them side by side.') }}</p><p class="mt-6"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button></p></div>
        @else
            <div class="mb-4 flex justify-end"><form method="POST" action="{{ route('compare.clear') }}">@csrf<x-ui.button variant="ghost" type="submit">{{ __('Clear all') }}</x-ui.button></form></div>
            <div class="overflow-x-auto rounded-card border border-warm-100" tabindex="0" role="region" aria-label="{{ __('Comparison table') }}"><table class="w-full min-w-[640px] text-left text-sm">
                <thead><tr><th scope="col" class="sticky left-0 w-36 bg-warm-50 p-3"><span class="sr-only">{{ __('Attribute') }}</span></th>@foreach ($properties as $p)<th scope="col" class="min-w-52 border-l border-warm-100 p-3 align-top"><a href="{{ route('properties.show', $p->slug) }}" class="block"><div class="aspect-[4/3] overflow-hidden rounded-lg"><x-picture :media="$p->coverMedia" :alt="$p->title" sizes="240px" /></div><span class="mt-2 block font-semibold text-navy-900">{{ $p->title }}</span></a>
                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-normal text-warm-700" x-data="compare({{ $p->id }}, true)"><input type="checkbox" class="h-4 w-4 accent-[#0e8f6e]" checked @change="toggle().then(() => location.reload())"> {{ __('Remove') }}</label></th>@endforeach</tr></thead>
                <tbody class="divide-y divide-warm-100">@foreach ($rows as [$label, $fn])<tr><th scope="row" class="sticky left-0 bg-warm-50 p-3 font-medium text-warm-700">{{ __($label) }}</th>@foreach ($properties as $p)<td class="border-l border-warm-100 p-3">{{ $fn($p) }}</td>@endforeach</tr>@endforeach
                    @foreach ($allAmenities as $a)<tr><th scope="row" class="sticky left-0 bg-warm-50 p-3 font-medium text-warm-700">{{ $a->displayName() }}</th>@foreach ($properties as $p)<td class="border-l border-warm-100 p-3">@if ($p->amenities->contains('id', $a->id))<x-ic name="check" class="h-5 w-5 text-brand-600" /><span class="sr-only">{{ __('Yes') }}</span>@else<span class="text-warm-300">—</span><span class="sr-only">{{ __('No') }}</span>@endif</td>@endforeach</tr>@endforeach</tbody></table></div>
        @endif
    </div>
</x-layouts.app>
