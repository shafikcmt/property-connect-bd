@props(['property' => null, 'project' => null])
<section id="visit" class="rounded-card border border-warm-100 bg-white p-6 shadow-sm" aria-labelledby="visit-title">
    <h2 id="visit-title" class="font-display text-xl font-semibold">{{ __('Request a site visit') }}</h2>
    <p class="mt-1 text-sm text-warm-700">{{ __('Pick a preferred day and time. We will confirm the final schedule with you.') }}</p>
    @if (session('form') === 'visit')
        <div class="mt-4 rounded-input border border-brand-600 bg-brand-600/5 p-4 text-sm text-navy-900" role="status"><x-ic name="check" class="mr-1 inline h-5 w-5 text-brand-600" />{{ session('status') }}</div>
    @else
        <form method="POST" action="{{ route('site-visit.store') }}" class="mt-4">
            @csrf
            @if ($property)<input type="hidden" name="property_id" value="{{ $property->id }}">@endif
            @if ($project)<input type="hidden" name="project_id" value="{{ $project->id }}">@endif
            <div class="mb-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="v-date">{{ __('Preferred date') }}</label><input class="input" type="date" id="v-date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ now()->addDay()->toDateString() }}" max="{{ now()->addDays(60)->toDateString() }}" required>@error('preferred_date')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="v-window">{{ __('Preferred time') }}</label><select class="select" id="v-window" name="preferred_window" required>@foreach (['morning' => 'Morning (9–12)', 'afternoon' => 'Afternoon (12–4)', 'evening' => 'Evening (4–7)'] as $k => $l)<option value="{{ $k }}" @selected(old('preferred_window') === $k)>{{ __($l) }}</option>@endforeach</select>@error('preferred_window')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            </div>
            <x-forms.contact-fields prefix="vis-" :message="false" />
            <x-ui.button type="submit" class="mt-5 w-full">{{ __('Request site visit') }}</x-ui.button>
        </form>
    @endif
</section>
