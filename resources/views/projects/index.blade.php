@php $label = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'ready' => 'Ready to move', 'completed' => 'Completed']; @endphp
<x-layouts.app :title="($status ? $label[$status].' projects' : 'New residential projects in Bangladesh')" :robots="$indexable ? 'index,follow' : 'noindex,follow'" description="Explore upcoming, ongoing and ready residential projects from partner developers, with unit types, handover dates and verified details."
    :breadcrumbs="[['Home', route('home')], ['Projects', null]]">
    <section class="bg-navy-900 text-white"><div class="container-x py-10 sm:py-14"><h1 class="font-display text-3xl font-semibold !text-white sm:text-4xl">{{ $status ? __($label[$status]).' '.__('projects') : __('Residential projects') }}</h1>
        <p class="mt-2 max-w-2xl text-warm-100/80">{{ __('Discover new developments from partner developers. Prices and availability are shown only when verified.') }}</p></div></section>
    <div class="container-x py-8">
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Project status') }}">
            @foreach ([null => 'All'] + $label as $k => $l)<a href="{{ $k ? route('projects.status', $k) : route('projects.index') }}" class="chip !px-4 !py-2 text-sm {{ $status === $k ? 'bg-navy-900 text-white' : 'bg-warm-100 text-warm-700 hover:bg-warm-300/60' }}" @if ($status === $k) aria-current="page" @endif>{{ __($l) }}</a>@endforeach
        </nav>
        <form method="GET" class="mt-5 grid gap-3 sm:grid-cols-[1.4fr_1fr_1fr_auto]">
            <div><label for="p-q" class="sr-only">{{ __('Search projects') }}</label><input id="p-q" class="input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search project name') }}"></div>
            <div><label for="p-loc" class="sr-only">{{ __('Location') }}</label><select id="p-loc" class="select" name="location"><option value="">{{ __('Any location') }}</option>@foreach ($locations as $l)<option value="{{ $l->path }}" @selected(($filters['location'] ?? '') === $l->path)>{{ $l->fullName() }}</option>@endforeach</select></div>
            <div><label for="p-dev" class="sr-only">{{ __('Developer') }}</label><select id="p-dev" class="select" name="developer"><option value="">{{ __('Any developer') }}</option>@foreach ($developers as $d)<option value="{{ $d->slug }}" @selected(($filters['developer'] ?? '') === $d->slug)>{{ $d->name }}</option>@endforeach</select></div>
            <x-ui.button type="submit"><x-ic name="search" class="h-5 w-5" />{{ __('Filter') }}</x-ui.button>
        </form>
        <div class="mt-8">
            @if ($projects->isEmpty())
                <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><x-ic name="building" class="mx-auto h-10 w-10 text-warm-500" /><h2 class="mt-4 font-display text-2xl font-semibold">{{ __('No projects found') }}</h2><p class="mt-2 text-warm-700">{{ __('Try a different status or location.') }}</p><p class="mt-6"><x-ui.button :href="route('projects.index')">{{ __('Show all projects') }}</x-ui.button></p></div>
            @else
                @php $favs = app(\App\Services\Favorites::class)->ids(request(), 'project'); @endphp
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">@foreach ($projects as $i => $p)<x-project-card :project="$p" :i="$i % 3" :favs="$favs" tag="h2" :eager="$loop->first" />@endforeach</div>
                <div class="mt-10">{{ $projects->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
