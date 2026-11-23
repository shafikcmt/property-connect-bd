<x-layouts.app title="Search" robots="noindex,follow" :breadcrumbs="[['Home', route('home')], ['Search', null]]">
    <x-page-hero eyebrow="Search" :title="$q !== '' ? 'Results for “'.$q.'”' : 'Search'" />
    <div class="container-x py-10">
        <form action="{{ route('search') }}" class="mb-10 flex max-w-2xl gap-2" role="search"><label for="sq" class="sr-only">{{ __('Search') }}</label><input id="sq" name="q" value="{{ $q }}" class="input" placeholder="{{ __('Search locations, projects, developers, properties…') }}" maxlength="100"><x-ui.button type="submit"><x-ic name="search" class="h-5 w-5" /><span class="sr-only">{{ __('Search') }}</span></x-ui.button></form>
        @if ($q === '')<p class="text-warm-700">{{ __('Type a location, project, developer or property name.') }}</p>
        @elseif ($properties->isEmpty() && $projects->isEmpty() && $developers->isEmpty() && $locations->isEmpty())
            <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><h2 class="font-display text-2xl font-semibold">{{ __('No results found') }}</h2><p class="mt-2 text-warm-700">{{ __('Try a different spelling or browse all properties.') }}</p><p class="mt-6"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button></p></div>
        @endif
        @if ($locations->isNotEmpty())<h2 class="font-display text-xl font-semibold">{{ __('Locations') }}</h2><ul class="mt-3 flex flex-wrap gap-2">@foreach ($locations as $l)<li><a class="chip !px-4 !py-2 text-sm bg-navy-50 hover:bg-navy-900 hover:text-white" href="{{ route('locations.show', $l->path) }}">{{ $l->fullName() }}</a></li>@endforeach</ul>@endif
        @if ($properties->isNotEmpty())<h2 class="mt-10 font-display text-xl font-semibold">{{ __('Properties') }}</h2><div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($properties as $i => $p)<x-property-card :property="$p" :i="$i % 3" tag="h3" />@endforeach</div>@endif
        @if ($projects->isNotEmpty())<h2 class="mt-10 font-display text-xl font-semibold">{{ __('Projects') }}</h2><div class="mt-4 grid gap-6 md:grid-cols-2 xl:grid-cols-3">@foreach ($projects as $i => $p)<x-project-card :project="$p" :i="$i % 3" />@endforeach</div>@endif
        @if ($developers->isNotEmpty())<h2 class="mt-10 font-display text-xl font-semibold">{{ __('Developers') }}</h2><div class="mt-4 grid grid-cols-2 gap-5 lg:grid-cols-4">@foreach ($developers as $i => $d)<x-developer-card :developer="$d" :i="$i % 4" />@endforeach</div>@endif
    </div>
</x-layouts.app>
