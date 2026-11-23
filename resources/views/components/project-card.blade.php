@props(['project', 'i' => 0, 'favs' => null])
@php
    $favs ??= app(\App\Services\Favorites::class)->ids(request(), 'project');
    $range = $project->publicPriceRange();
    $types = $project->unitTypes->pluck('name')->take(3);
    $statusColor = ['upcoming' => 'bg-gold-500 text-navy-900', 'ongoing' => 'bg-info text-white', 'ready' => 'bg-brand-600 text-white', 'completed' => 'bg-warm-700 text-white'][$project->status->value];
@endphp
<article class="reveal group relative overflow-hidden rounded-card border border-warm-100 bg-white lift" style="--i: {{ $i }}">
    <div class="relative">
        <div class="zoom-wrap aspect-[16/11]"><x-picture :media="$project->coverMedia" :alt="$project->name" sizes="(min-width:1024px) 30vw, (min-width:640px) 45vw, 100vw" /></div>
        <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 via-navy-900/0 to-transparent"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between p-3">
            <div class="flex flex-wrap gap-1.5">
                <span class="chip {{ $statusColor }}">{{ $project->status->label() }}</span>
                @if ($project->isFeaturedNow())<span class="chip bg-gold-500 text-navy-900"><x-ic name="star" class="h-3.5 w-3.5" />{{ __('Featured') }}</span>@endif
                @if ($project->is_verified)<span class="chip bg-brand-600 text-white"><x-ic name="badge" class="h-3.5 w-3.5" />{{ __('Verified') }}</span>@endif
            </div>
            <div class="pointer-events-auto"><x-fav-button kind="project" :id="$project->id" :active="in_array($project->id, $favs)" /></div>
        </div>
        <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between gap-3 text-white">
            <p class="line-clamp-1 text-sm font-medium drop-shadow">{{ $project->developer?->name }}</p>
            @if ($project->developer?->logo)<span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-white p-1"><x-picture :media="$project->developer->logo" :alt="$project->developer->name" fit="object-contain" /></span>@endif
        </div>
    </div>
    <div class="p-5">
        <h3 class="font-display text-xl font-semibold text-navy-900"><a href="{{ route('projects.show', $project->slug) }}" class="after:absolute after:inset-0 after:content-['']">{{ $project->name }}</a></h3>
        @if ($project->location)<p class="mt-1 flex items-center gap-1.5 text-sm text-warm-700"><x-ic name="pin" class="h-4 w-4 text-brand-600" />{{ $project->location->fullName() }}</p>@endif
        @if ($types->isNotEmpty())<p class="mt-3 line-clamp-1 text-sm text-warm-700">{{ $types->implode(' · ') }}</p>@endif
        <div class="mt-4 flex items-end justify-between border-t border-warm-100 pt-4">
            <div>
                @if ($range)<p class="text-xs text-warm-500">{{ __('Starting from') }}</p><p class="font-display text-lg font-semibold text-navy-900">{{ \App\Support\Money::short($range['from']) }}</p>
                @else<p class="text-sm text-warm-700">{{ __('Price on request') }}</p>@endif
            </div>
            <div class="text-right text-xs text-warm-700">
                @if ($project->handover_on)<p class="flex items-center justify-end gap-1"><x-ic name="calendar" class="h-3.5 w-3.5" />{{ __('Handover') }} {{ $project->handover_on->format('M Y') }}</p>@endif
                <span class="mt-1 inline-flex items-center gap-1 font-semibold text-brand-700 transition group-hover:gap-2">{{ __('View project') }}<x-ic name="arrow" class="h-4 w-4" /></span>
            </div>
        </div>
    </div>
</article>
