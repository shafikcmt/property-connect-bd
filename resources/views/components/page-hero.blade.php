@props(['eyebrow' => null, 'title', 'sub' => null])
<section class="relative overflow-hidden bg-navy-900 text-white"><div class="pointer-events-none absolute inset-0 opacity-40" style="background: radial-gradient(50% 80% at 90% 0%, rgba(20,184,166,.3), transparent 60%)"></div>
    <div class="container-x relative py-12 sm:py-16 hero-in">@if ($eyebrow)<p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-gold-500" style="--i:0">{{ __($eyebrow) }}</p>@endif
        <h1 class="max-w-3xl font-display text-3xl font-semibold leading-tight !text-white sm:text-5xl" style="--i:1">{{ __($title) }}</h1>@if ($sub)<p class="mt-4 max-w-2xl text-lg text-warm-100/85" style="--i:2">{{ __($sub) }}</p>@endif</div></section>
