@props(['developer', 'i' => 0])
<article class="reveal group relative flex flex-col items-center rounded-card border border-warm-100 bg-white p-6 text-center lift" style="--i: {{ $i }}">
    <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-navy-50 p-2"><x-picture :media="$developer->logo" :alt="$developer->name" fit="object-contain" sizes="120px" /></div>
    <h3 class="mt-4 font-display text-lg font-semibold text-navy-900"><a href="{{ route('developers.show', $developer->slug) }}" class="after:absolute after:inset-0 after:content-['']">{{ $developer->name }}</a></h3>
    <div class="mt-2 flex flex-wrap items-center justify-center gap-1.5">
        @if ($developer->is_verified)<span class="chip bg-brand-600/10 text-brand-700"><x-ic name="badge" class="h-3.5 w-3.5" />{{ __('Verified') }}</span>@endif
        @if ($developer->publicEstablishedYear())<span class="chip bg-warm-100 text-warm-700">{{ __('Est.') }} {{ $developer->publicEstablishedYear() }}</span>@endif
    </div>
    @if ($developer->summary)<p class="mt-3 line-clamp-2 text-sm text-warm-700">{{ $developer->summary }}</p>@endif
    <p class="mt-4 text-sm font-semibold text-brand-700">{{ trans_choice(':count project|:count projects', $developer->projects_count ?? 0) }}</p>
</article>
