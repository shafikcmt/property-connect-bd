@php
    $r = $this->getReport();
    $pct = fn ($n, $d) => $d > 0 ? round($n / $d * 100).'%' : '—';
    $fmtMin = function ($m) { if ($m === null) return '—'; return $m < 90 ? round($m).' min' : ($m < 2880 ? round($m / 60, 1).' h' : round($m / 1440, 1).' days'); };
    $th = 'text-align:left;padding:.5rem .75rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;opacity:.7';
    $td = 'padding:.5rem .75rem;border-top:1px solid rgba(128,128,128,.2)';
@endphp
<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem">
        <label for="range" style="font-weight:600">Period</label>
        <select id="range" wire:model.live="range" style="padding:.4rem .6rem;border:1px solid rgba(128,128,128,.4);border-radius:.5rem;background:transparent">
            @foreach (\App\Filament\Pages\CrmReports::ranges() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
        <span style="opacity:.7;font-size:.85rem">Leads created {{ $r['period']['from']->format('d M Y') }} – {{ $r['period']['to']->format('d M Y') }}. Counts come only from recorded activity.</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
        @foreach ([
            ['New leads', $r['total'], $r['repeat'].' repeat inquiries merged'],
            ['Contacted', $r['response']['responded'], $pct($r['response']['responded'], $r['total']).' of new leads'],
            ['Median first response', $fmtMin($r['response']['median_minutes']), $pct($r['response']['within_sla'], $r['response']['responded']).' within '.$r['response']['sla_minutes'].' min'],
            ['Still waiting', $r['response']['waiting'], 'new, not yet contacted'],
            ['Converted', $r['funnel']['Converted'], $pct($r['funnel']['Converted'], $r['total']).' of new leads'],
            ['Unassigned now', $r['unassigned_now'], 'open leads with no owner'],
        ] as [$label, $value, $hint])
            <x-filament::section><div style="font-size:.8rem;opacity:.7">{{ $label }}</div><div style="font-size:1.8rem;font-weight:700">{{ $value }}</div><div style="font-size:.8rem;opacity:.7">{{ $hint }}</div></x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Pipeline (stages recorded for these leads)">
        <table style="width:100%;border-collapse:collapse"><thead><tr><th style="{{ $th }}">Stage</th><th style="{{ $th }}">Leads</th><th style="{{ $th }}">Of new leads</th></tr></thead><tbody>
            @foreach ($r['funnel'] as $stage => $n)<tr><td style="{{ $td }}">{{ $stage }}</td><td style="{{ $td }}"><strong>{{ $n }}</strong></td><td style="{{ $td }}">{{ $pct($n, $r['total']) }}</td></tr>@endforeach
        </tbody></table>
        <p style="margin-top:.5rem;font-size:.8rem;opacity:.7">A lead that skips a stage is not counted in the skipped one.</p>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
        <x-filament::section heading="Site visits">
            <table style="width:100%;border-collapse:collapse"><tbody>
                @foreach ($r['visits'] as $label => $n)<tr><td style="{{ $td }}">{{ $label }}</td><td style="{{ $td }}"><strong>{{ $n }}</strong></td></tr>@endforeach
                <tr><td style="{{ $td }}">Leads with a completed visit</td><td style="{{ $td }}"><strong>{{ $r['visit_leads'] }}</strong> ({{ $pct($r['visit_leads'], $r['total']) }})</td></tr>
            </tbody></table>
        </x-filament::section>

        <x-filament::section heading="Why leads were lost">
            @if ($r['lost'])<table style="width:100%;border-collapse:collapse"><tbody>@foreach ($r['lost'] as $reason => $n)<tr><td style="{{ $td }}">{{ $reason }}</td><td style="{{ $td }}"><strong>{{ $n }}</strong></td></tr>@endforeach</tbody></table>
            @else<p style="opacity:.7">No closed leads with a reason in this period.</p>@endif
        </x-filament::section>
    </div>

    <x-filament::section heading="Where leads come from">
        <table style="width:100%;border-collapse:collapse"><thead><tr><th style="{{ $th }}">Source</th><th style="{{ $th }}">Leads</th><th style="{{ $th }}">Contacted</th><th style="{{ $th }}">Converted</th></tr></thead><tbody>
            @forelse ($r['sources'] as $s)<tr><td style="{{ $td }}">{{ $s['source'] }}</td><td style="{{ $td }}"><strong>{{ $s['leads'] }}</strong></td><td style="{{ $td }}">{{ $s['contacted'] }}</td><td style="{{ $td }}">{{ $s['converted'] }}</td></tr>@empty<tr><td style="{{ $td }}" colspan="4">No leads in this period.</td></tr>@endforelse
        </tbody></table>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
        <x-filament::section heading="By agent">
            <table style="width:100%;border-collapse:collapse"><thead><tr><th style="{{ $th }}">Agent</th><th style="{{ $th }}">Assigned</th><th style="{{ $th }}">Contacted</th><th style="{{ $th }}">Converted</th></tr></thead><tbody>
                @forelse ($r['agents'] as $a)<tr><td style="{{ $td }}">{{ $a['agent'] }}</td><td style="{{ $td }}"><strong>{{ $a['assigned'] }}</strong></td><td style="{{ $td }}">{{ $a['contacted'] }}</td><td style="{{ $td }}">{{ $a['converted'] }}</td></tr>@empty<tr><td style="{{ $td }}" colspan="4">No assigned leads yet.</td></tr>@endforelse
            </tbody></table>
        </x-filament::section>

        <x-filament::section heading="Most asked about">
            @foreach ([['Properties', $r['top_properties']], ['Projects', $r['top_projects']]] as [$title, $rows])
                <div style="font-size:.75rem;text-transform:uppercase;opacity:.7;margin-top:.5rem">{{ $title }}</div>
                @forelse ($rows as $row)<div style="display:flex;justify-content:space-between;{{ $td }}"><span>{{ $row['name'] }}</span><strong>{{ $row['n'] }}</strong></div>@empty<p style="opacity:.7">None yet.</p>@endforelse
            @endforeach
        </x-filament::section>
    </div>
</x-filament-panels::page>
