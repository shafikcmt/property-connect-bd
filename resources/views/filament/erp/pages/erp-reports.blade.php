@php
    $r = $this->getReport();
    $m = fn ($v) => '৳ '.number_format((float) $v);
    $th = 'text-align:left;padding:.5rem .75rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;opacity:.7';
    $td = 'padding:.5rem .75rem;border-top:1px solid rgba(128,128,128,.2)';
@endphp
<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem">
        <label for="range" style="font-weight:600">Period</label>
        <select id="range" wire:model.live="range" style="padding:.4rem .6rem;border:1px solid rgba(128,128,128,.4);border-radius:.5rem;background:transparent">
            @foreach (\App\Filament\Erp\Pages\ErpReports::ranges() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
        <span style="opacity:.7;font-size:.85rem">{{ $r['period']['from']->format('d M Y') }} – {{ $r['period']['to']->format('d M Y') }}. Figures come from recorded payments and instalment schedules (reversals are subtracted).</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
        <x-filament::section><div style="font-size:.8rem;opacity:.7">Collected in period</div><div style="font-size:1.8rem;font-weight:700">{{ $m($r['collected']) }}</div></x-filament::section>
        <x-filament::section><div style="font-size:.8rem;opacity:.7">Overdue now</div><div style="font-size:1.8rem;font-weight:700">{{ $m($r['ageing_total']) }}</div><div style="font-size:.8rem;opacity:.7">{{ count($r['rows']) }} shareholders behind</div></x-filament::section>
    </div>

    <x-filament::section heading="Overdue by age (days past due date)">
        <table style="width:100%;border-collapse:collapse"><thead><tr>@foreach ($r['ageing'] as $label => $v)<th style="{{ $th }}">{{ $label }}</th>@endforeach</tr></thead>
            <tbody><tr>@foreach ($r['ageing'] as $v)<td style="{{ $td }};font-weight:600">{{ $m($v) }}</td>@endforeach</tr></tbody></table>
    </x-filament::section>

    <x-filament::section heading="Who is behind">
        @if (count($r['rows']) === 0)
            <p style="opacity:.7">Nobody is overdue.</p>
        @else
            <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th style="{{ $th }}">Shareholder</th><th style="{{ $th }}">Project · share</th><th style="{{ $th }}">Oldest (days)</th><th style="{{ $th }}">Overdue</th></tr></thead><tbody>
                @foreach (array_slice($r['rows'], 0, 25) as $row)
                    <tr><td style="{{ $td }}"><a href="{{ \App\Filament\Erp\Resources\ShareHoldings\ShareHoldingResource::getUrl('view', ['record' => $row['holding_id']]) }}">{{ $row['shareholder'] }}</a></td><td style="{{ $td }}">{{ $row['project'] }} · #{{ $row['share'] }}</td><td style="{{ $td }}">{{ $row['oldest_days'] }}</td><td style="{{ $td }};font-weight:600">{{ $m($row['overdue']) }}</td></tr>
                @endforeach
            </tbody></table></div>
            @if (count($r['rows']) > 25)<p style="opacity:.7;font-size:.85rem">Showing the 25 largest. The download has everyone.</p>@endif
        @endif
    </x-filament::section>

    <x-filament::section heading="Collected, by month and by payment method">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem">
            <table style="width:100%;border-collapse:collapse"><tbody>@forelse ($r['by_month'] as $month => $v)<tr><td style="{{ $td }}">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</td><td style="{{ $td }};text-align:right">{{ $m($v) }}</td></tr>@empty<tr><td style="opacity:.7">No payments in this period.</td></tr>@endforelse</tbody></table>
            <table style="width:100%;border-collapse:collapse"><tbody>@foreach ($r['by_method'] as $method => $v)<tr><td style="{{ $td }}">{{ ucwords(str_replace('_', ' ', $method)) }}</td><td style="{{ $td }};text-align:right">{{ $m($v) }}</td></tr>@endforeach</tbody></table>
        </div>
    </x-filament::section>

    <x-filament::section heading="By project">
        <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse"><thead><tr>@foreach (['Project', 'Shares sold', 'Total due', 'Paid', 'Balance', 'Overdue'] as $h)<th style="{{ $th }}">{{ $h }}</th>@endforeach</tr></thead><tbody>
            @forelse ($r['projects'] as $p)<tr><td style="{{ $td }}">{{ $p['project'] }}</td><td style="{{ $td }}">{{ $p['sold'] }}</td><td style="{{ $td }}">{{ $m($p['due']) }}</td><td style="{{ $td }}">{{ $m($p['paid']) }}</td><td style="{{ $td }}">{{ $m($p['balance']) }}</td><td style="{{ $td }}">{{ $m($p['overdue']) }}</td></tr>@empty<tr><td style="opacity:.7;padding:.5rem">No active sales yet.</td></tr>@endforelse
        </tbody></table></div>
    </x-filament::section>

    <x-filament::section heading="Download (opens in Excel)">
        <div style="display:flex;flex-wrap:wrap;gap:.75rem">
            <x-filament::button tag="a" color="gray" icon="heroicon-o-arrow-down-tray" :href="route('erp.export', ['report' => 'collections', 'days' => $this->range])">Collections (CSV)</x-filament::button>
            <x-filament::button tag="a" color="gray" icon="heroicon-o-arrow-down-tray" :href="route('erp.export', ['report' => 'overdue'])">Overdue list (CSV)</x-filament::button>
        </div>
        <p style="opacity:.7;font-size:.8rem;margin-top:.5rem">Downloads contain shareholder names and phone numbers. Keep them private; each download is recorded.</p>
    </x-filament::section>
</x-filament-panels::page>
