@if ($project->updates->isNotEmpty())
<section id="updates" class="mt-10" aria-labelledby="upd-h">
    <h2 id="upd-h" class="font-display text-2xl font-semibold">{{ __('Project updates') }}</h2>
    <ol class="mt-5 space-y-6 border-l-2 border-warm-100 pl-6">
        @foreach ($project->updates as $u)
            <li class="relative"><span class="absolute -left-[31px] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand-600 ring-2 ring-brand-600/30" aria-hidden="true"></span>
                <time datetime="{{ $u->happened_on->toDateString() }}" class="text-xs font-semibold uppercase tracking-wide text-warm-700">{{ $u->happened_on->translatedFormat('d M Y') }}</time>
                <p class="mt-0.5 font-semibold text-navy-900">{{ $u->tr('title') }}</p>
                @if ($u->tr('body'))<p class="mt-1 whitespace-pre-line text-warm-700">{{ $u->tr('body') }}</p>@endif</li>
        @endforeach
    </ol>
</section>
@endif
