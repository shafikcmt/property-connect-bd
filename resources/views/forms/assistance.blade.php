<x-forms.shell title="Get real estate assistance" sub="Tell us what you are looking for — budget, areas and timeline — and we will help you shortlist options.">
    @if (session('status') && ! session('form'))<div class="rounded-card border border-brand-600 bg-brand-600/5 p-8 text-center" role="status"><x-ic name="check" class="mx-auto h-10 w-10 text-brand-600" /><h2 class="mt-3 font-display text-2xl font-semibold">{{ __('Request received') }}</h2><p class="mt-2 text-warm-700">{{ session('status') }}</p><p class="mt-6"><x-ui.button :href="route('home')">{{ __('Back to home') }}</x-ui.button></p></div>
    @else
    <form method="POST" action="{{ route('requests.store', 'assistance') }}" class="rounded-card border border-warm-100 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="ar">{{ __('Preferred area(s)') }}</label><input id="ar" class="input" name="area" value="{{ old('area') }}" maxlength="150"></div>
            <div><label class="label" for="pt">{{ __('Property type') }}</label><select id="pt" name="property_type" class="select"><option value="">{{ __('Any') }}</option>@foreach ($types as $t)<option value="{{ $t->name }}">{{ $t->displayName() }}</option>@endforeach</select></div>
            <div><label class="label" for="bd">{{ __('Bedrooms') }}</label><input id="bd" class="input" type="number" min="0" max="30" name="bedrooms" value="{{ old('bedrooms') }}"></div>
            <div><label class="label" for="ep">{{ __('Budget (৳)') }}</label><input id="ep" class="input" type="number" min="0" name="expected_price" value="{{ old('expected_price') }}"></div>
        </div>
        <x-forms.contact-fields prefix="rq-" message-label="Anything else we should know?" />
        <x-ui.button type="submit" class="mt-6 w-full sm:w-auto">{{ __('Submit request') }}</x-ui.button>
    </form>
    @endif
</x-forms.shell>
