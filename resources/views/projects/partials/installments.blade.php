@if ($project->installments->isNotEmpty())
<section id="installments" class="mt-10" aria-labelledby="inst-h">
    <h2 id="inst-h" class="font-display text-2xl font-semibold">{{ __('Instalment schedule') }}</h2>
    <div class="mt-4 overflow-x-auto rounded-card border border-warm-100" tabindex="0" role="region" aria-label="{{ __('Instalment schedule table') }}">
        <table class="w-full min-w-[480px] text-left text-sm">
            <thead class="bg-warm-50 text-xs uppercase tracking-wide text-warm-500"><tr><th scope="col" class="p-3">{{ __('Instalment') }}</th><th scope="col" class="p-3">{{ __('When') }}</th><th scope="col" class="p-3 text-right">{{ __('Amount') }}</th></tr></thead>
            <tbody class="divide-y divide-warm-100">
                @foreach ($project->installments as $i)
                    <tr><th scope="row" class="p-3 font-semibold text-navy-900">{{ $i->label }}@if ($i->note)<span class="mt-0.5 block text-xs font-normal text-warm-700">{{ $i->note }}</span>@endif</th><td class="p-3">{{ $i->timing ?: '—' }}</td><td class="p-3 text-right font-semibold">{{ $i->amount !== null ? \App\Support\Money::short($i->amount) : '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-warm-700">{{ __('Amounts and dates are provided by the developer and may change. The signed agreement prevails.') }}</p>
</section>
@endif
