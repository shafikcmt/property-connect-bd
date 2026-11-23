<x-forms.shell title="Sell your property" sub="Tell us about your property and our team will contact you to discuss the best way to sell.">
    @if (session('status') && ! session('form'))<div class="rounded-card border border-brand-600 bg-brand-600/5 p-8 text-center" role="status"><x-ic name="check" class="mx-auto h-10 w-10 text-brand-600" /><h2 class="mt-3 font-display text-2xl font-semibold">{{ __('Request received') }}</h2><p class="mt-2 text-warm-700">{{ session('status') }}</p><p class="mt-6"><x-ui.button :href="route('home')">{{ __('Back to home') }}</x-ui.button></p></div>
    @else
    <form method="POST" action="{{ route('requests.store', 'sell') }}" class="rounded-card border border-warm-100 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="pt">{{ __('Property type') }}</label><select id="pt" name="property_type" class="select"><option value="">{{ __('Select') }}</option>@foreach ($types as $t)<option value="{{ $t->name }}" @selected(old('property_type') === $t->name)>{{ $t->name }}</option>@endforeach</select></div>
            <div><label class="label" for="ar">{{ __('Area / location') }}</label><input id="ar" class="input" name="area" value="{{ old('area') }}" maxlength="150" placeholder="{{ __('e.g. Gulshan, Dhaka') }}"></div>
            <div><label class="label" for="sz">{{ __('Size (sqft)') }}</label><input id="sz" class="input" type="number" min="1" name="size_sqft" value="{{ old('size_sqft') }}"></div>
            <div><label class="label" for="bd">{{ __('Bedrooms') }}</label><input id="bd" class="input" type="number" min="0" max="30" name="bedrooms" value="{{ old('bedrooms') }}"></div>
            <div class="sm:col-span-2"><label class="label" for="ep">{{ __('Expected price (৳, optional)') }}</label><input id="ep" class="input" type="number" min="0" name="expected_price" value="{{ old('expected_price') }}"></div>
        </div>
        <x-forms.contact-fields prefix="rq-" message-label="Anything else we should know?" />
        <x-ui.button type="submit" class="mt-6 w-full sm:w-auto">{{ __('Submit request') }}</x-ui.button>
    </form>
    @endif
</x-forms.shell>
