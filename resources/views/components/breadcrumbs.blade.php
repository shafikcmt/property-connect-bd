@props(['items'])
<nav aria-label="{{ __('Breadcrumb') }}" class="container-x pt-6 text-sm text-warm-700">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as [$label, $url])
            <li class="flex items-center gap-1.5">
                @if ($url && ! $loop->last)<a href="{{ $url }}" class="hover:text-navy-900 hover:underline">{{ $label }}</a><x-ic name="arrow" class="h-3.5 w-3.5 text-warm-300" />
                @else<span class="font-medium text-navy-900" aria-current="page">{{ $label }}</span>@endif
            </li>
        @endforeach
    </ol>
</nav>
