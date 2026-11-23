@props(['options', 'purpose' => 'sale', 'popular' => collect()])
@php
    $budgets = collect(['sale' => [5000000, 10000000, 20000000, 50000000, 100000000], 'rent' => [20000, 40000, 80000, 150000, 300000]])
        ->map(fn ($list) => collect($list)->map(fn ($n) => [$n, \App\Support\Money::short($n)])->all())->all();
@endphp
<form action="{{ route('properties.index') }}" method="GET" role="search" aria-label="{{ __('Property search') }}"
      x-data="{ p: '{{ $purpose }}', upTo: {{ \Illuminate\Support\Js::from(__('Up to')) }}, budgets: {{ \Illuminate\Support\Js::from($budgets) }} }"
      class="rounded-3xl border border-white/60 bg-white p-4 text-left shadow-2xl shadow-navy-900/35 ring-1 ring-navy-900/5 sm:p-5">
    <input type="hidden" name="purpose" :value="p">
    <div class="mb-4 inline-flex rounded-full bg-warm-100 p-1" role="tablist" aria-label="{{ __('Buy or rent') }}">
        <button type="button" role="tab" :aria-selected="(p === 'sale').toString()" @click="p = 'sale'" class="rounded-full px-6 py-2 text-sm font-semibold transition" :class="p === 'sale' ? 'bg-navy-900 text-white shadow' : 'text-warm-700'">{{ __('Buy') }}</button>
        <button type="button" role="tab" :aria-selected="(p === 'rent').toString()" @click="p = 'rent'" class="rounded-full px-6 py-2 text-sm font-semibold transition" :class="p === 'rent' ? 'bg-navy-900 text-white shadow' : 'text-warm-700'">{{ __('Rent') }}</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_auto]">
        <div class="relative"><label for="hs-loc" class="sr-only">{{ __('Location') }}</label>
            <x-ic name="pin" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-600" />
            <select id="hs-loc" name="location" class="select !pl-11"><option value="">{{ __('Any location') }}</option>@foreach ($options['locations'] as $l)<option value="{{ $l->path }}">{{ $l->fullName() }}</option>@endforeach</select></div>
        <div class="relative"><label for="hs-type" class="sr-only">{{ __('Property type') }}</label>
            <x-ic name="home" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-600" />
            <select id="hs-type" name="type[]" class="select !pl-11"><option value="">{{ __('Any type') }}</option>@foreach ($options['types'] as $t)<option value="{{ $t->slug }}">{{ $t->displayName() }}</option>@endforeach</select></div>
        <div class="relative"><label for="hs-budget" class="sr-only">{{ __('Maximum budget') }}</label>
            <x-ic name="layers" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-600" />
            <select id="hs-budget" name="price_max" class="select !pl-11"><option value="">{{ __('Any budget') }}</option><template x-for="b in budgets[p]" :key="p + b[0]"><option :value="b[0]" x-text="upTo + ' ' + b[1]"></option></template></select></div>
        <button class="inline-flex h-12 items-center justify-center gap-2 rounded-input bg-brand-600 px-7 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 hover:shadow-brand-700/30"><x-ic name="search" class="h-5 w-5" />{{ __('Search') }}</button>
    </div>
    @if ($popular->isNotEmpty())
    <p class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-warm-700"><span class="font-medium text-warm-900">{{ __('Popular:') }}</span>
        @foreach ($popular as $l)<a href="{{ route('locations.show', $l->path) }}" class="rounded-full bg-warm-50 px-3 py-1 font-medium text-navy-900 ring-1 ring-warm-100 transition hover:bg-brand-600 hover:text-white hover:ring-brand-600">{{ $l->displayName() }}</a>@endforeach
    </p>
    @endif
</form>
