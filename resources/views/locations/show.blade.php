@php
    $crumbs = [['Home', route('home')], ['Locations', route('locations.index')]];
    if ($location->parent) { $crumbs[] = [$location->parent->displayName(), $location->parent->type->value !== 'division' ? route('locations.show', $location->parent->path) : null]; }
    $crumbs[] = [$location->displayName(), null];
    $desc = $location->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $location->description), 155);
    $favs = app(\App\Services\Favorites::class)->ids(request(), 'property');
@endphp
<x-layouts.app :title="$location->seo_title ?: 'Properties & projects in '.$location->displayName()" :description="$desc" :robots="$indexable ? 'index,follow' : 'noindex,follow'" :breadcrumbs="$crumbs">
    <section class="bg-navy-900 text-white"><div class="container-x py-12 sm:py-16"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-500">{{ $location->type->label() }}</p><h1 class="mt-2 font-display text-3xl font-semibold !text-white sm:text-5xl">{{ $location->displayName() }}</h1><p class="mt-2 text-warm-100/80">{{ $location->fullName() }}</p></div></section>
    <x-breadcrumbs :items="$crumbs" />
    <div class="container-x py-10">
        @if ($location->description)<div class="max-w-3xl text-lg text-warm-700">{{ $location->description }}</div>@endif
        @if ($types->isNotEmpty())<ul class="mt-6 flex flex-wrap gap-2">@foreach ($types as $t)<li><a class="chip !px-4 !py-2 text-sm bg-navy-50 text-navy-900 hover:bg-navy-900 hover:text-white" href="{{ route('properties.index', ['location' => $location->path, 'type' => [$t->slug]]) }}">{{ $t->displayName() }} ({{ $t->total }})</a></li>@endforeach</ul>@endif
        @if ($children->isNotEmpty())<div class="mt-10"><h2 class="font-display text-2xl font-semibold">{{ __('Areas in :n', ['n' => $location->displayName()]) }}</h2><ul class="mt-4 flex flex-wrap gap-2">@foreach ($children as $c)<li><a class="chip !px-4 !py-2 text-sm bg-warm-100 hover:bg-warm-300/60" href="{{ route('locations.show', $c->path) }}">{{ $c->displayName() }} · {{ $c->listings_count }}</a></li>@endforeach</ul></div>@endif
        @if ($properties->isNotEmpty())<section class="mt-12"><div class="flex items-end justify-between"><h2 class="font-display text-2xl font-semibold">{{ __('Properties in :n', ['n' => $location->displayName()]) }}</h2><a class="text-sm font-semibold text-brand-700" href="{{ route('properties.index', ['location' => $location->path]) }}">{{ __('View all') }} →</a></div><div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($properties as $i => $p)<x-property-card :property="$p" :i="$i % 3" :favs="$favs" />@endforeach</div></section>@endif
        @if ($projects->isNotEmpty())<section class="mt-14"><h2 class="font-display text-2xl font-semibold">{{ __('Projects in :n', ['n' => $location->displayName()]) }}</h2><div class="mt-5 grid gap-6 md:grid-cols-2 xl:grid-cols-3">@foreach ($projects as $i => $p)<x-project-card :project="$p" :i="$i % 3" />@endforeach</div></section>@endif
        @if ($properties->isEmpty() && $projects->isEmpty())<div class="mt-10 rounded-card border border-dashed border-warm-300 bg-warm-50 p-10 text-center"><h2 class="font-display text-2xl font-semibold">{{ __('No listings here yet') }}</h2><p class="mt-2 text-warm-700">{{ __('Tell us what you are looking for and we will keep an eye out.') }}</p><p class="mt-5"><x-ui.button :href="route('forms.assistance')">{{ __('Get buying help') }}</x-ui.button></p></div>@endif
    </div>
</x-layouts.app>
