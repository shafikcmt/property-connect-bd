@props(['images', 'title'])
@php $n = $images->count(); @endphp
@if ($n === 0)
    <div class="flex aspect-[16/9] items-center justify-center rounded-card bg-navy-50 text-navy-700/40"><x-ic name="building" class="h-16 w-16" /></div>
@else
<div x-data="gallery({{ $n }})" @keydown.window.arrow-right="box && next()" @keydown.window.arrow-left="box && prev()" @keydown.window.escape="box && close()">
    <div class="grid gap-2 {{ $n > 1 ? 'md:grid-cols-4 md:grid-rows-2' : '' }}">
        <button type="button" class="group relative overflow-hidden rounded-card {{ $n > 1 ? 'md:col-span-2 md:row-span-2' : '' }}" @click="open(0)" aria-label="{{ $n }} {{ __('photos') }} — {{ __('open photo gallery') }}">
            <div class="zoom-wrap aspect-[4/3] md:aspect-auto md:h-full md:min-h-[420px]"><x-picture :media="$images[0]" :alt="$title" sizes="(min-width:768px) 50vw, 100vw" :eager="true" /></div>
            <span class="absolute bottom-3 left-3 chip bg-navy-900/80 text-white"><x-ic name="camera" class="h-3.5 w-3.5" />{{ $n }} {{ __('photos') }}</span>
        </button>
        @foreach ($images->slice(1, 4) as $k => $img)
            <button type="button" class="group relative hidden overflow-hidden rounded-card md:block" @click="open({{ $k + 1 }})" aria-label="{{ __('Open photo :n', ['n' => $k + 2]) }}">
                <div class="zoom-wrap h-full min-h-[200px]"><x-picture :media="$img" :alt="$title" sizes="25vw" /></div>
                @if ($loop->last && $n > 5)<span class="absolute inset-0 flex items-center justify-center bg-navy-900/60 text-lg font-semibold text-white">+{{ $n - 5 }}</span>@endif
            </button>
        @endforeach
    </div>
    <div x-show="box" x-cloak x-transition.opacity class="fixed inset-0 z-[80] flex flex-col bg-navy-900/95" role="dialog" aria-modal="true" aria-label="{{ __('Photo gallery') }}" @touchstart="ts($event)" @touchend="te($event)">
        <div class="flex items-center justify-between p-4 text-white"><p class="text-sm" aria-live="polite"><span x-text="i + 1"></span> / {{ $n }}</p><button type="button" @click="close()" class="flex h-11 w-11 items-center justify-center rounded-full hover:bg-white/10" aria-label="{{ __('Close gallery') }}"><x-ic name="x" /></button></div>
        <div class="relative flex flex-1 items-center justify-center px-4 pb-4">
            <button type="button" @click="prev()" class="absolute left-3 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25" aria-label="{{ __('Previous photo') }}"><x-ic name="arrow" class="h-6 w-6 rotate-180" /></button>
            @foreach ($images as $k => $img)
                <img x-show="i === {{ $k }}" x-cloak x-transition.opacity.duration.200ms src="{{ $img->url(1600) }}" alt="{{ $img->altText($title) }}" class="max-h-[78vh] max-w-full rounded-lg object-contain" loading="lazy">
            @endforeach
            <button type="button" @click="next()" class="absolute right-3 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25" aria-label="{{ __('Next photo') }}"><x-ic name="arrow" class="h-6 w-6" /></button>
        </div>
    </div>
</div>
@endif
