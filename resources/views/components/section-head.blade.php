@props(['eyebrow' => null, 'title', 'sub' => null, 'align' => 'left'])
<div {{ $attributes->merge(['class' => 'reveal mb-10 flex flex-wrap items-end justify-between gap-4 '.($align === 'center' ? 'flex-col items-center text-center' : '')]) }}>
    <div class="max-w-2xl">
        @if ($eyebrow)<p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">{{ $eyebrow }}</p>@endif
        <h2 class="font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $title }}</h2>
        @if ($sub)<p class="mt-3 text-warm-700">{{ $sub }}</p>@endif
    </div>
    {{ $slot }}
</div>
