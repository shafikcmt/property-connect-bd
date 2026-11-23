@props(['options', 'purpose' => 'sale'])
<form action="{{ route('properties.index') }}" method="GET" role="search" aria-label="{{ __('Property search') }}"
      x-data="{ p: '{{ $purpose }}', budgets: { sale: [[5000000,'৳ 50 Lac'],[10000000,'৳ 1 Cr'],[20000000,'৳ 2 Cr'],[50000000,'৳ 5 Cr'],[100000000,'৳ 10 Cr']], rent: [[20000,'৳ 20k'],[40000,'৳ 40k'],[80000,'৳ 80k'],[150000,'৳ 1.5 Lac'],[300000,'৳ 3 Lac']] } }"
      class="rounded-2xl bg-white p-3 text-left shadow-2xl shadow-navy-900/30 sm:p-4">
    <input type="hidden" name="purpose" :value="p">
    <div class="mb-3 inline-flex rounded-full bg-warm-100 p-1" role="tablist" aria-label="{{ __('Buy or rent') }}">
        <button type="button" role="tab" :aria-selected="(p === 'sale').toString()" @click="p = 'sale'" class="rounded-full px-5 py-2 text-sm font-semibold transition" :class="p === 'sale' ? 'bg-navy-900 text-white shadow' : 'text-warm-700'">{{ __('Buy') }}</button>
        <button type="button" role="tab" :aria-selected="(p === 'rent').toString()" @click="p = 'rent'" class="rounded-full px-5 py-2 text-sm font-semibold transition" :class="p === 'rent' ? 'bg-navy-900 text-white shadow' : 'text-warm-700'">{{ __('Rent') }}</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_auto]">
        <div><label for="hs-loc" class="sr-only">{{ __('Location') }}</label>
            <select id="hs-loc" name="location" class="select"><option value="">{{ __('Any location') }}</option>@foreach ($options['locations'] as $l)<option value="{{ $l->path }}">{{ $l->fullName() }}</option>@endforeach</select></div>
        <div><label for="hs-type" class="sr-only">{{ __('Property type') }}</label>
            <select id="hs-type" name="type[]" class="select"><option value="">{{ __('Any type') }}</option>@foreach ($options['types'] as $t)<option value="{{ $t->slug }}">{{ $t->name }}</option>@endforeach</select></div>
        <div><label for="hs-budget" class="sr-only">{{ __('Maximum budget') }}</label>
            <select id="hs-budget" name="price_max" class="select"><option value="">{{ __('Any budget') }}</option><template x-for="b in budgets[p]" :key="p + b[0]"><option :value="b[0]" x-text="'Up to ' + b[1]"></option></template></select></div>
        <button class="inline-flex h-12 items-center justify-center gap-2 rounded-input bg-brand-600 px-6 text-sm font-semibold text-white transition hover:bg-brand-700"><x-ic name="search" class="h-5 w-5" />{{ __('Search') }}</button>
    </div>
</form>
