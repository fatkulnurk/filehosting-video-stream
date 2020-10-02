<div class="pagination is-centered" role="navigation" aria-label="pagination">
    {{-- Previous Page Link --}}
    @if (!$paginator->onFirstPage())
        <a class="pagination-previous" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">@lang('pagination.previous')</a>
    @endif

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <a class="pagination-next" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">@lang('pagination.next')</a>
    @endif



    <ul class="pagination-list">

        <li><a href="{{ $paginator->url(1) }}" class="pagination-link" aria-label="Goto page 1">1</a></li>
        <li><span class="pagination-ellipsis">&hellip;</span></li>

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
{{--            @if (is_string($element))--}}
{{--                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>--}}
{{--            @endif--}}

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li aria-current="page"><span class="pagination-link is-current">{{ $page }}</span></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        <li><span class="pagination-ellipsis">&hellip;</span></li>
        <li><a href="{{ $paginator->url($paginator->lastPage()) }}" class="pagination-link" aria-label="Goto page 86">{{ $paginator->lastPage() }}</a></li>
    </ul>
</div>
