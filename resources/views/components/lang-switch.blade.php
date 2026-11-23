@props(['drawer' => false])
@php $cur = app()->getLocale(); $other = $cur === 'bn' ? 'en' : 'bn'; @endphp
@if ($drawer)
    <div class="mb-3 flex items-center gap-2 text-sm" role="group" aria-label="{{ __('Language') }}">
        <span class="text-warm-700">{{ __('Language') }}:</span>
        @foreach (config('pcbd.locales') as $code => $name)
            <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" rel="nofollow" @if ($cur === $code) aria-current="true" @endif
               class="rounded-full px-4 py-2 font-semibold {{ $cur === $code ? 'bg-navy-900 text-white' : 'bg-warm-100 text-navy-900 hover:bg-warm-300/60' }}">{{ $name }}</a>
        @endforeach
    </div>
@else
    <a href="{{ route('locale.switch', $other) }}" lang="{{ $other }}" hreflang="{{ $other }}" rel="nofollow"
       class="hidden h-11 min-w-11 items-center justify-center rounded-full px-3 text-sm font-semibold text-navy-900 transition hover:bg-warm-100 sm:inline-flex"
       aria-label="{{ $other === 'bn' ? 'বাংলায় দেখুন' : 'View in English' }}" title="{{ $other === 'bn' ? 'বাংলায় দেখুন' : 'View in English' }}">{{ $other === 'bn' ? 'বাংলা' : 'EN' }}</a>
@endif
