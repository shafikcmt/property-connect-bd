@props(['property', 'i' => 0, 'list' => false, 'favs' => null, 'cmp' => null])
@php
    $favs ??= app(\App\Services\Favorites::class)->ids(request(), 'property');
    $cmp ??= session('compare', []);
    $rent = $property->purpose === \App\Enums\Purpose::Rent;
    $loc = $property->location?->fullName();
@endphp
<article class="reveal group relative overflow-hidden rounded-card border border-warm-100 bg-white lift {{ $list ? 'sm:flex' : '' }}" style="--i: {{ $i }}">
    <div class="relative {{ $list ? 'sm:w-2/5 sm:shrink-0' : '' }}">
        <div class="zoom-wrap aspect-[4/3] {{ $list ? 'sm:h-full sm:aspect-auto' : '' }}">
            <x-picture :media="$property->coverMedia" :alt="$property->title" sizes="(min-width:1024px) 30vw, (min-width:640px) 45vw, 100vw" />
        </div>
        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between p-3">
            <div class="flex flex-wrap gap-1.5">
                @if ($property->isFeaturedNow())<span class="chip bg-gold-500 text-navy-900"><x-ic name="star" class="h-3.5 w-3.5" />{{ __('Featured') }}</span>@endif
                @if ($property->is_verified)<span class="chip bg-brand-600 text-white"><x-ic name="badge" class="h-3.5 w-3.5" />{{ __('Verified') }}</span>@endif
                <span class="chip bg-white/95 text-navy-900">{{ $rent ? __('For rent') : __('For sale') }}</span>
            </div>
            <div class="pointer-events-auto"><x-fav-button kind="property" :id="$property->id" :active="in_array($property->id, $favs)" /></div>
        </div>
        @if ($property->images_count > 1)<span class="absolute bottom-3 left-3 chip bg-navy-900/80 text-white"><x-ic name="camera" class="h-3.5 w-3.5" />{{ $property->images_count }}</span>@endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <p class="font-display text-2xl font-semibold text-navy-900">{{ \App\Support\Money::priceLabel($property->price ? (float) $property->price : null, $property->price_negotiable, $rent, $property->rent_period) }}
            @if ($property->price_negotiable && $property->price)<span class="ml-1 align-middle text-xs font-sans font-medium text-warm-500">{{ __('Negotiable') }}</span>@endif</p>
        <h3 class="mt-1 line-clamp-1 text-base font-semibold text-navy-900"><a href="{{ route('properties.show', $property->slug) }}" class="after:absolute after:inset-0 after:content-[''] focus-visible:after:outline focus-visible:after:outline-2">{{ $property->title }}</a></h3>
        @if ($loc)<p class="mt-1 flex items-center gap-1.5 text-sm text-warm-700"><x-ic name="pin" class="h-4 w-4 shrink-0 text-brand-600" /><span class="line-clamp-1">{{ $loc }}</span></p>@endif
        <ul class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-warm-100 pt-4 text-sm text-warm-700">
            @if ($property->bedrooms !== null)<li class="flex items-center gap-1.5" title="{{ __('Bedrooms') }}"><x-ic name="bed" class="h-4 w-4" />{{ $property->bedrooms }} <span class="sr-only">{{ __('bedrooms') }}</span></li>@endif
            @if ($property->bathrooms !== null)<li class="flex items-center gap-1.5" title="{{ __('Bathrooms') }}"><x-ic name="bath" class="h-4 w-4" />{{ $property->bathrooms }} <span class="sr-only">{{ __('bathrooms') }}</span></li>@endif
            @if ($property->size_sqft)<li class="flex items-center gap-1.5"><x-ic name="area" class="h-4 w-4" />{{ number_format((float) $property->size_sqft) }} sqft</li>@endif
            <li class="ml-auto text-xs font-medium text-warm-500">{{ $property->type?->name }}</li>
        </ul>
        <div class="mt-4 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition group-hover:gap-2.5">{{ __('View details') }}<x-ic name="arrow" class="h-4 w-4" /></span>
            <label class="relative z-10 inline-flex cursor-pointer items-center gap-1.5 text-xs text-warm-700" x-data="compare({{ $property->id }}, {{ in_array($property->id, $cmp) ? 'true' : 'false' }})" title="{{ __('Compare') }}">
                <input type="checkbox" class="h-4 w-4 rounded border-warm-300 accent-[#0e8f6e]" :checked="on" @change="toggle()"> {{ __('Compare') }}
                <span x-show="msg" x-cloak x-text="msg" class="absolute bottom-full right-0 mb-2 w-44 rounded bg-navy-900 p-2 text-xs text-white"></span>
            </label>
        </div>
    </div>
</article>
