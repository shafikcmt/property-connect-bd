@props(['media' => null, 'alt' => '', 'sizes' => '(min-width:1024px) 33vw, 100vw', 'eager' => false, 'ratio' => 'aspect-[4/3]', 'fit' => 'object-cover'])
@if ($media && $media->isImage())
    <img src="{{ $media->url(960) }}" srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}"
         alt="{{ $media->altText($alt) }}" width="{{ $media->width }}" height="{{ $media->height }}"
         @if ($eager) fetchpriority="high" @else loading="lazy" decoding="async" @endif
         {{ $attributes->merge(['class' => "h-full w-full {$fit}"]) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex h-full w-full items-center justify-center bg-navy-50 text-navy-700/40']) }} role="img" aria-label="{{ $alt }}"><x-ic name="building" class="h-12 w-12" /></div>
@endif
