@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php
    $base = 'inline-flex min-h-11 items-center whitespace-nowrap justify-center gap-2 rounded-input px-5 py-2.5 text-sm font-semibold transition-colors disabled:opacity-60 disabled:cursor-not-allowed';
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700',
        'secondary' => 'border border-navy-900 text-navy-900 hover:bg-navy-50',
        'ghost' => 'text-navy-900 hover:bg-warm-100',
        'danger' => 'bg-danger text-white hover:opacity-90',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
