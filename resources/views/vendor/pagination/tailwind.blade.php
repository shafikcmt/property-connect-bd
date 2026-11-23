@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-11 min-w-11 items-center justify-center rounded-input border px-3 text-sm font-semibold transition';
    @endphp
    <nav role="navigation" aria-label="{{ __('Pagination') }}" class="flex flex-col items-center gap-3">
        <ul class="flex flex-wrap items-center justify-center gap-1.5">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $base }} cursor-not-allowed border-warm-100 text-warm-300" aria-disabled="true"><span class="sr-only">{{ __('Previous page') }}</span><span aria-hidden="true">‹</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} border-warm-300 text-navy-900 hover:bg-navy-50"><span class="sr-only">{{ __('Previous page') }}</span><span aria-hidden="true">‹</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-flex h-11 items-center px-1 text-warm-500" aria-hidden="true">…</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $base }} border-navy-900 bg-navy-900 text-white" aria-current="page"><span class="sr-only">{{ __('Page') }} </span>{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $base }} border-warm-300 text-navy-900 hover:bg-navy-50"><span class="sr-only">{{ __('Go to page') }} </span>{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} border-warm-300 text-navy-900 hover:bg-navy-50"><span class="sr-only">{{ __('Next page') }}</span><span aria-hidden="true">›</span></a>
                @else
                    <span class="{{ $base }} cursor-not-allowed border-warm-100 text-warm-300" aria-disabled="true"><span class="sr-only">{{ __('Next page') }}</span><span aria-hidden="true">›</span></span>
                @endif
            </li>
        </ul>
        <p class="text-sm text-warm-700">{{ __('Showing :from–:to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
    </nav>
@endif
