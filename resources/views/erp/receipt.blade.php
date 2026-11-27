@extends('erp.layout')
@section('title', 'Receipt '.$payment->receipt_no)
@section('content')
    <h1>{{ $payment->developer->name }}</h1>
    <div class="muted">{{ $payment->isReversal() ? 'Reversal note' : 'Payment receipt' }}</div>

    <table style="margin-top:20px">
        <tr><th>Receipt no.</th><td><strong>{{ $payment->receipt_no }}</strong></td><th>Date received</th><td>{{ $payment->received_on->format('d M Y') }}</td></tr>
        <tr><th>Received from</th><td>{{ $payment->holding->shareholder->name }}</td><th>Project</th><td>{{ $payment->holding->share->project->name }}</td></tr>
        <tr><th>Share</th><td>#{{ $payment->holding->share->number }}</td><th>Method</th><td>{{ $payment->method->label() }}@if ($payment->reference) · {{ $payment->reference }}@endif</td></tr>
    </table>

    <div style="margin:28px 0">
        <div class="muted">{{ $payment->isReversal() ? 'Amount reversed' : 'Amount received' }}</div>
        <div class="big">৳ {{ number_format((float) $payment->amount, 2) }}</div>
        @if ($payment->isReversal())<p>This note cancels receipt <strong>{{ $payment->reverses->receipt_no }}</strong>. Reason: {{ $payment->note }}</p>@elseif ($payment->note)<p class="muted">{{ $payment->note }}</p>@endif
    </div>

    <table>
        <tr><th>Total due on this share</th><td class="n">৳ {{ number_format($ledger->totalDue(), 2) }}</td></tr>
        <tr><th>Paid to date (all receipts)</th><td class="n">৳ {{ number_format($ledger->paid(), 2) }}</td></tr>
        <tr><th>Balance</th><td class="n"><strong>৳ {{ number_format($ledger->balance(), 2) }}</strong></td></tr>
    </table>

    <div class="foot">Recorded by {{ $payment->recorder?->name ?? '—' }} on {{ $payment->created_at->format('d M Y H:i') }}. This is a computer-generated record; it is not a legal title document.</div>
@endsection
