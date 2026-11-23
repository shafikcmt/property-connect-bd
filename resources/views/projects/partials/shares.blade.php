@php $sh = $project->shareSummary(); @endphp
@if ($sh)
<section id="shares" class="mt-10" aria-labelledby="shares-h">
    <h2 id="shares-h" class="font-display text-2xl font-semibold">{{ __('Land shares') }}</h2>
    <p class="mt-2 text-warm-700">{{ __('This project sells land shares. Each share is offered together with a flat in the building to be built, on the terms of the developer’s agreement.') }}</p>
    <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-warm-100 bg-warm-100 sm:grid-cols-3">
        @foreach (array_filter([
            ['Total shares', $sh['total']],
            ['Sold', $sh['sold']],
            ['Remaining', $sh['remaining']],
            ['Price per share', $sh['price'] ? \App\Support\Money::short($sh['price']) : null],
            ['Land area', $sh['land'] ? rtrim(rtrim(number_format($sh['land'], 3), '0'), '.').' '.__('decimal') : null],
            ['Buildings', $sh['buildings']],
        ], fn ($r) => $r[1] !== null && $r[1] !== '') as [$k, $v])
            <div class="bg-white p-4"><dt class="text-xs uppercase tracking-wide text-warm-500">{{ __($k) }}</dt><dd class="mt-1 font-display text-xl font-semibold text-navy-900">{{ $v }}</dd></div>
        @endforeach
    </dl>
    @if ($sh['registered'] !== null)
        @php $base = $sh['sold'] ?: $sh['total']; $pct = $base ? min(100, (int) round($sh['registered'] / $base * 100)) : 0; @endphp
        <div class="mt-5">
            <p class="text-sm font-semibold text-navy-900">{{ __('Registered deeds') }}: {{ $sh['registered'] }} {{ __('of') }} {{ $base }} {{ __('shareholders') }}</p>
            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-warm-100" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Registered deeds') }}"><div class="h-full rounded-full bg-brand-600" style="width: {{ $pct }}%"></div></div>
        </div>
    @endif
</section>
@endif
