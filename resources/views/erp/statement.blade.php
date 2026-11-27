@extends('erp.layout')
@section('title', 'Statement — '.$holding->shareholder->name)
@section('content')
    <h1>{{ $holding->developer->name }}</h1>
    <div class="muted">Account statement · printed {{ now()->format('d M Y') }}</div>

    <table style="margin-top:20px">
        <tr><th>Shareholder</th><td>{{ $holding->shareholder->name }}</td><th>Project</th><td>{{ $holding->share->project->name }}</td></tr>
        <tr><th>Share</th><td>#{{ $holding->share->number }}</td><th>Sale date</th><td>{{ $holding->sold_on->format('d M Y') }} <span class="tag">{{ $holding->status }}</span></td></tr>
        <tr><th>Agreed price</th><td>৳ {{ number_format((float) $holding->price, 2) }}</td><th>National ID</th><td>{{ $holding->shareholder->maskedNid() }}</td></tr>
    </table>

    <h2>Instalments</h2>
    <table>
        <thead><tr><th>Instalment</th><th>Due</th><th class="n">Amount</th><th class="n">Paid</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($ledger->rows() as $r)
            <tr><td>{{ $r['instalment']->label }}</td><td>{{ $r['instalment']->due_on->format('d M Y') }}</td><td class="n">{{ number_format((float) $r['instalment']->amount, 2) }}</td><td class="n">{{ number_format($r['paid'], 2) }}</td><td>{{ ucfirst($r['status']) }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">No instalment schedule.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if ($payments->isNotEmpty())
        <h2>Payments</h2>
        <table>
            <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th class="n">Amount</th></tr></thead>
            <tbody>@foreach ($payments as $p)<tr><td>{{ $p->receipt_no }}@if ($p->isReversal()) <span class="tag">reversal</span>@endif</td><td>{{ $p->received_on->format('d M Y') }}</td><td>{{ $p->method->label() }}{{ $p->reference ? ' · '.$p->reference : '' }}</td><td class="n">{{ number_format($p->signedAmount(), 2) }}</td></tr>@endforeach</tbody>
        </table>
    @endif

    <h2>Summary</h2>
    <table>
        <tr><th>Total due</th><td class="n">৳ {{ number_format($ledger->totalDue(), 2) }}</td></tr>
        <tr><th>Paid</th><td class="n">৳ {{ number_format($ledger->paid(), 2) }}</td></tr>
        <tr><th>Balance</th><td class="n"><strong>৳ {{ number_format($ledger->balance(), 2) }}</strong></td></tr>
        <tr><th>Overdue now</th><td class="n">৳ {{ number_format($ledger->overdue(), 2) }}</td></tr>
    </table>
    <div class="foot">Computer-generated statement. It reflects the payments recorded by {{ $holding->developer->name }}; it is not a legal title document.</div>
@endsection
