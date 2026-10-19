@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pg pg-off">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pg" rel="prev">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg pg-off">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pg pg-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pg">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pg" rel="next">&rsaquo;</a>
        @else
            <span class="pg pg-off">&rsaquo;</span>
        @endif
    </nav>
@endif