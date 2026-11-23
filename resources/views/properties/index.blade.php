@php
    $f = $filters;
    $title = match (true) { ($purposePage ?? null) === 'for-rent' => 'Properties for rent in Bangladesh', ($purposePage ?? null) === 'for-sale' => 'Properties for sale in Bangladesh', default => 'Properties for sale and rent in Bangladesh' };
    $q = request()->except('page');
    $chips = [];
    foreach ($f as $k => $v) { if ($k === 'sort') continue; foreach ((array) $v as $one) { $chips[] = [$k, $one]; } }
    $labelFor = function ($k, $v) use ($options) {
        return match ($k) {
            'type' => $options['types']->firstWhere('slug', $v)?->name ?? $v,
            'location' => $options['locations']->firstWhere('path', $v)?->fullName() ?? $v,
            'developer' => $options['developers']->firstWhere('slug', $v)?->tr('name') ?? $v,
            'purpose' => __($v === 'rent' ? 'For rent' : 'For sale'),
            'price_min' => __('Min').' '.\App\Support\Money::short($v), 'price_max' => __('Max').' '.\App\Support\Money::short($v),
            'beds' => $v.'+ '.__('beds'), 'baths' => $v.'+ '.__('baths'), 'size_min' => __('Min').' '.$v.' '.__('sqft'), 'size_max' => __('Max').' '.$v.' '.__('sqft'),
            'verified' => __('Verified only'), 'q' => '“'.$v.'”', 'amenities' => $options['amenities']->firstWhere('slug', $v)?->displayName() ?? $v,
            default => __(ucfirst(str_replace('_', ' ', $k))).': '.__(str_replace('_', ' ', $v)),
        };
    };
    $without = function ($k, $v) use ($q) { $c = $q; if (is_array($c[$k] ?? null)) { $c[$k] = array_values(array_diff($c[$k], [$v])); } else { unset($c[$k]); } return url()->current().'?'.http_build_query($c); };
@endphp
<x-layouts.app :title="$title" :robots="$indexable ? 'index,follow' : 'noindex,follow'" :canonical="url()->current()" :description="'Browse '.$properties->total().' verified properties. Filter by location, price, size and bedrooms, compare listings and request a site visit.'"
    :breadcrumbs="[['Home', route('home')], ['Properties', null]]">
    <section class="bg-navy-900 text-white"><div class="container-x py-10 sm:py-14">
        <h1 class="font-display text-3xl font-semibold !text-white sm:text-4xl">{{ __($title) }}</h1>
        <p class="mt-2 text-warm-100/80">{{ trans_choice(':count property found|:count properties found', $properties->total()) }}</p>
    </div></section>

    <div class="container-x py-8" x-data="{ filters: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-input border border-warm-300 px-4 text-sm font-semibold lg:hidden" @click="filters = true"><x-ic name="filter" class="h-5 w-5" />{{ __('Filters') }} @if (count($chips))<span class="rounded-full bg-brand-600 px-2 text-xs text-white">{{ count($chips) }}</span>@endif</button>
            <form method="GET" class="ml-auto flex items-center gap-2" x-data>
                @foreach ($q as $k => $v) @if (! in_array($k, ['sort', 'view'])) @foreach ((array) $v as $one)<input type="hidden" name="{{ $k }}{{ is_array($v) ? '[]' : '' }}" value="{{ $one }}">@endforeach @endif @endforeach
                <label class="sr-only" for="sort">{{ __('Sort by') }}</label>
                <select id="sort" name="sort" class="select !h-11 w-auto min-w-44" @change="$el.form.submit()">@foreach (\App\Services\PropertySearch::SORTS as $k => $l)<option value="{{ $k }}" @selected(($f['sort'] ?? 'featured') === $k)>{{ __($l) }}</option>@endforeach</select>
                <noscript><button class="rounded-input border px-3 py-2 text-sm">{{ __('Apply') }}</button></noscript>
                <div class="hidden gap-1 rounded-input border border-warm-300 p-1 sm:flex" role="group" aria-label="{{ __('Layout') }}">
                    <a href="{{ url()->current().'?'.http_build_query($q + ['view' => 'grid']) }}" class="rounded p-2 {{ $view === 'grid' ? 'bg-navy-900 text-white' : 'text-warm-700 hover:bg-warm-100' }}" aria-label="{{ __('Grid view') }}" @if ($view === 'grid') aria-current="true" @endif><x-ic name="grid" class="h-5 w-5" /></a>
                    <a href="{{ url()->current().'?'.http_build_query($q + ['view' => 'list']) }}" class="rounded p-2 {{ $view === 'list' ? 'bg-navy-900 text-white' : 'text-warm-700 hover:bg-warm-100' }}" aria-label="{{ __('List view') }}" @if ($view === 'list') aria-current="true" @endif><x-ic name="list" class="h-5 w-5" /></a>
                </div>
            </form>
        </div>
        @if ($chips)
        <ul class="mt-4 flex flex-wrap gap-2" aria-label="{{ __('Active filters') }}">
            @foreach ($chips as [$k, $v])<li><a href="{{ $without($k, $v) }}" class="chip bg-navy-50 text-navy-900 hover:bg-navy-900 hover:text-white">{{ $labelFor($k, $v) }}<x-ic name="x" class="h-3.5 w-3.5" /><span class="sr-only">{{ __('Remove filter') }}</span></a></li>@endforeach
            <li><a href="{{ route('properties.index') }}" class="chip text-brand-700 underline">{{ __('Clear all') }}</a></li>
        </ul>
        @endif

        <div class="mt-6 grid gap-8 lg:grid-cols-[300px_1fr]">
            {{-- Filters --}}
            <aside class="hidden lg:block" :class="filters && '!fixed !block inset-0 z-[70] overflow-y-auto bg-white p-5'" aria-label="{{ __('Filters') }}">
                <div class="mb-4 flex items-center justify-between lg:hidden"><h2 class="font-display text-xl font-semibold">{{ __('Filters') }}</h2><button type="button" @click="filters = false" class="h-11 w-11" aria-label="{{ __('Close filters') }}"><x-ic name="x" class="mx-auto" /></button></div>
                <form method="GET" action="{{ url()->current() }}" class="space-y-5 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pr-2 no-scrollbar">
                    @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
                    <div><label class="label" for="f-q">{{ __('Keyword or ID') }}</label><input id="f-q" class="input" name="q" value="{{ $f['q'] ?? '' }}" maxlength="100" placeholder="{{ __('e.g. Gulshan, PC-1004') }}"></div>
                    @unless ($purposePage ?? null)<div><span class="label">{{ __('Looking to') }}</span><div class="grid grid-cols-3 gap-2 text-sm">@foreach ([['', 'Any'], ['sale', 'Buy'], ['rent', 'Rent']] as [$v, $l])<label class="cursor-pointer"><input type="radio" class="peer sr-only" name="purpose" value="{{ $v }}" @checked(($f['purpose'] ?? '') === $v)><span class="block rounded-input border border-warm-300 py-2 text-center peer-checked:border-navy-900 peer-checked:bg-navy-900 peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2">{{ __($l) }}</span></label>@endforeach</div></div>@endunless
                    <div><label class="label" for="f-loc">{{ __('Location') }}</label><select id="f-loc" class="select" name="location"><option value="">{{ __('Any location') }}</option>@foreach ($options['locations'] as $l)<option value="{{ $l->path }}" @selected(($f['location'] ?? '') === $l->path)>{{ $l->fullName() }}</option>@endforeach</select></div>
                    <fieldset><legend class="label">{{ __('Property type') }}</legend><div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-sm">@foreach ($options['types'] as $t)<label class="flex items-center gap-2"><input type="checkbox" name="type[]" value="{{ $t->slug }}" class="h-4 w-4 accent-[#0e8f6e]" @checked(in_array($t->slug, (array) ($f['type'] ?? [])))> {{ $t->displayName() }}</label>@endforeach</div></fieldset>
                    <div><span class="label">{{ __('Price (৳)') }}</span><div class="grid grid-cols-2 gap-2"><input class="input" type="number" min="0" name="price_min" value="{{ $f['price_min'] ?? '' }}" placeholder="{{ __('Min') }}" aria-label="{{ __('Minimum price') }}"><input class="input" type="number" min="0" name="price_max" value="{{ $f['price_max'] ?? '' }}" placeholder="{{ __('Max') }}" aria-label="{{ __('Maximum price') }}"></div></div>
                    <div><span class="label">{{ __('Size (sqft)') }}</span><div class="grid grid-cols-2 gap-2"><input class="input" type="number" min="0" name="size_min" value="{{ $f['size_min'] ?? '' }}" placeholder="{{ __('Min') }}" aria-label="{{ __('Minimum size') }}"><input class="input" type="number" min="0" name="size_max" value="{{ $f['size_max'] ?? '' }}" placeholder="{{ __('Max') }}" aria-label="{{ __('Maximum size') }}"></div></div>
                    <div class="grid grid-cols-2 gap-3"><div><label class="label" for="f-beds">{{ __('Bedrooms') }}</label><select id="f-beds" class="select" name="beds"><option value="">{{ __('Any') }}</option>@foreach ([1,2,3,4,5] as $n)<option value="{{ $n }}" @selected(($f['beds'] ?? '') == $n)>{{ $n }}+</option>@endforeach</select></div>
                        <div><label class="label" for="f-baths">{{ __('Bathrooms') }}</label><select id="f-baths" class="select" name="baths"><option value="">{{ __('Any') }}</option>@foreach ([1,2,3,4] as $n)<option value="{{ $n }}" @selected(($f['baths'] ?? '') == $n)>{{ $n }}+</option>@endforeach</select></div></div>
                    <details class="group rounded-input border border-warm-100 p-3" @if (collect($f)->only(['status','furnishing','facing','parking','floor_min','floor_max','developer','amenities'])->filter()->isNotEmpty()) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold">{{ __('More filters') }}<x-ic name="chevron" class="h-4 w-4 transition group-open:rotate-180" /></summary>
                        <div class="mt-4 space-y-4">
                            <div><label class="label" for="f-status">{{ __('Status') }}</label><select id="f-status" class="select" name="status"><option value="">{{ __('Any') }}</option>@foreach (\App\Enums\PropertyStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($f['status'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
                            <div><label class="label" for="f-furn">{{ __('Furnishing') }}</label><select id="f-furn" class="select" name="furnishing"><option value="">{{ __('Any') }}</option>@foreach (\App\Enums\Furnishing::options() as $v => $l)<option value="{{ $v }}" @selected(($f['furnishing'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
                            <div><label class="label" for="f-face">{{ __('Facing') }}</label><select id="f-face" class="select" name="facing"><option value="">{{ __('Any') }}</option>@foreach (\App\Enums\Facing::options() as $v => $l)<option value="{{ $v }}" @selected(($f['facing'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
                            <div class="grid grid-cols-2 gap-2"><div><label class="label" for="f-fmin">{{ __('Floor from') }}</label><input id="f-fmin" class="input" type="number" name="floor_min" value="{{ $f['floor_min'] ?? '' }}"></div><div><label class="label" for="f-fmax">{{ __('Floor to') }}</label><input id="f-fmax" class="input" type="number" name="floor_max" value="{{ $f['floor_max'] ?? '' }}"></div></div>
                            <div><label class="label" for="f-park">{{ __('Parking spaces (min)') }}</label><input id="f-park" class="input" type="number" min="0" name="parking" value="{{ $f['parking'] ?? '' }}"></div>
                            <div><label class="label" for="f-dev">{{ __('Developer') }}</label><select id="f-dev" class="select" name="developer"><option value="">{{ __('Any developer') }}</option>@foreach ($options['developers'] as $d)<option value="{{ $d->slug }}" @selected(($f['developer'] ?? '') === $d->slug)>{{ $d->name }}</option>@endforeach</select></div>
                            <fieldset><legend class="label">{{ __('Amenities') }}</legend><div class="grid gap-1.5 text-sm">@foreach ($options['amenities'] as $a)<label class="flex items-center gap-2"><input type="checkbox" name="amenities[]" value="{{ $a->slug }}" class="h-4 w-4 accent-[#0e8f6e]" @checked(in_array($a->slug, (array) ($f['amenities'] ?? [])))> {{ $a->displayName() }}</label>@endforeach</div></fieldset>
                        </div>
                    </details>
                    <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="verified" value="1" class="h-4 w-4 accent-[#0e8f6e]" @checked(! empty($f['verified']))> <x-ic name="badge" class="h-4 w-4 text-brand-600" />{{ __('Verified listings only') }}</label>
                    <div class="flex gap-2 pb-2"><x-ui.button type="submit" class="flex-1" @click="filters = false">{{ __('Show results') }}</x-ui.button><a href="{{ route('properties.index') }}" class="inline-flex min-h-11 items-center rounded-input border border-warm-300 px-4 text-sm font-semibold">{{ __('Reset') }}</a></div>
                </form>
                @auth
                <form method="POST" action="{{ route('account.searches.store') }}" class="mt-4 rounded-input border border-warm-100 p-3 text-sm" x-data="{ open: false }">@csrf
                    <button type="button" class="font-semibold text-brand-700" @click="open = !open">{{ __('Save this search') }}</button>
                    <div x-show="open" x-cloak class="mt-3 space-y-2"><input class="input" name="name" required maxlength="100" placeholder="{{ __('Name, e.g. 3 bed in Gulshan') }}" aria-label="{{ __('Search name') }}">@foreach ($f as $k => $v) @foreach ((array) $v as $one)<input type="hidden" name="filters[{{ $k }}]{{ is_array($v) ? '[]' : '' }}" value="{{ $one }}">@endforeach @endforeach<x-ui.button type="submit" class="w-full">{{ __('Save') }}</x-ui.button></div></form>
                @endauth
            </aside>

            {{-- Results --}}
            <div>
                @if ($properties->isEmpty())
                    <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><x-ic name="search" class="mx-auto h-10 w-10 text-warm-500" /><h2 class="mt-4 font-display text-2xl font-semibold">{{ __('No properties match your filters') }}</h2><p class="mt-2 text-warm-700">{{ __('Try removing a filter or widening your budget. You can also tell us what you need and we will help.') }}</p>
                        <p class="mt-6 flex flex-wrap justify-center gap-3"><x-ui.button :href="route('properties.index')">{{ __('Clear filters') }}</x-ui.button><x-ui.button variant="secondary" :href="route('forms.assistance')">{{ __('Ask for help') }}</x-ui.button></p></div>
                @else
                    @php $favs = app(\App\Services\Favorites::class)->ids(request(), 'property'); $cmp = session('compare', []); @endphp
                    <div class="grid gap-6 {{ $view === 'list' ? '' : 'sm:grid-cols-2 xl:grid-cols-3' }}">@foreach ($properties as $i => $p)<x-property-card :property="$p" :i="$i % 3" :list="$view === 'list'" :favs="$favs" :cmp="$cmp" tag="h2" :eager="$loop->first" />@endforeach</div>
                    <div class="mt-10">{{ $properties->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
