@php
    $desc = \Illuminate\Support\Str::limit(strip_tags((string) ($developer->tr('summary') ?: $developer->tr('about'))), 155);
    $crumbs = [['Home', route('home')], ['Developers', route('developers.index')], [$developer->tr('name'), null]];
    $schema = [array_filter(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $developer->tr('name'), 'url' => route('developers.show', $developer->slug), 'description' => $desc, 'foundingDate' => $developer->publicEstablishedYear() ? (string) $developer->publicEstablishedYear() : null])];
    $labels = ['ongoing' => 'Ongoing projects', 'upcoming' => 'Upcoming projects', 'ready' => 'Ready projects', 'completed' => 'Completed projects'];
@endphp
<x-layouts.app :title="$developer->seo_title ?: $developer->tr('name').' — projects & profile'" :description="$developer->seo_description ?: $desc" :schema="$schema" :breadcrumbs="$crumbs">
    <x-breadcrumbs :items="$crumbs" />
    <div class="container-x mt-6 grid gap-10 pb-16 lg:grid-cols-[1fr_380px]">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-5"><div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl border border-warm-100 bg-white p-3"><x-picture :media="$developer->logo" :alt="$developer->tr('name')" fit="object-contain" sizes="120px" :eager="true" /></div>
                <div><h1 class="font-display text-3xl font-semibold sm:text-4xl">{{ $developer->tr('name') }}</h1><div class="mt-2 flex flex-wrap gap-2">@if ($developer->is_verified)<span class="chip bg-brand-600 text-white"><x-ic name="badge" class="h-3.5 w-3.5" />{{ __('Verified') }}</span>@endif @if ($developer->publicEstablishedYear())<span class="chip bg-warm-100 text-warm-700">{{ __('Established') }} {{ $developer->publicEstablishedYear() }}</span>@endif</div></div></div>
            @if ($developer->tr('about'))<section class="mt-8"><h2 class="font-display text-2xl font-semibold">{{ __('About') }}</h2><div class="prose-pc mt-3 whitespace-pre-line">{{ $developer->tr('about') }}</div></section>@endif
            @if ($developer->show_contact_publicly || $developer->website || $developer->address)<section class="mt-8"><h2 class="font-display text-2xl font-semibold">{{ __('Contact') }}</h2><ul class="mt-3 space-y-2 text-warm-700">
                @if ($developer->address)<li class="flex gap-2"><x-ic name="pin" class="h-5 w-5 text-brand-600" />{{ $developer->address }}</li>@endif
                @if ($developer->website)<li><a class="font-semibold text-brand-700 hover:underline" href="{{ $developer->website }}" rel="noopener nofollow" target="_blank">{{ parse_url($developer->website, PHP_URL_HOST) ?: $developer->website }}</a></li>@endif
                @if ($developer->show_contact_publicly && $developer->phone)<li><a href="tel:{{ preg_replace('/[^+\d]/', '', $developer->phone) }}">{{ $developer->phone }}</a></li>@endif
                @if ($developer->show_contact_publicly && $developer->email)<li><a href="mailto:{{ $developer->email }}">{{ $developer->email }}</a></li>@endif</ul></section>@endif
            @foreach ($labels as $key => $l)@if (($projects[$key] ?? collect())->isNotEmpty())<section class="mt-12"><h2 class="font-display text-2xl font-semibold">{{ __($l) }}</h2><div class="mt-5 grid gap-6 md:grid-cols-2">@foreach ($projects[$key] as $i => $p)<x-project-card :project="$p" :i="$i" />@endforeach</div></section>@endif @endforeach
            @if ($projects->isEmpty())<p class="mt-10 rounded-card bg-warm-50 p-6 text-warm-700">{{ __('No published projects yet.') }}</p>@endif
        </div>
        <aside class="lg:sticky lg:top-24 lg:self-start"><x-inquiry-form :developer="$developer" title="Contact via Property Connect BD" cta="Send inquiry" /></aside>
    </div>
</x-layouts.app>
