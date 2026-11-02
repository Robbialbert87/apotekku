@if ($paginator->hasPages())
    <div class="pagination-wrap">
        <div class="pagination-info">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </div>
        <nav aria-label="Navigasi halaman">
            <ul class="pagination-custom">
                @if ($paginator->onFirstPage())
                    <li><span class="page-btn is-disabled"><i class="bi bi-chevron-left"></i></span></li>
                @else
                    <li><a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i></a></li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="page-btn is-disabled">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li><span class="page-btn is-active">{{ $page }}</span></li>
                            @else
                                <li><a class="page-btn" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li><a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="bi bi-chevron-right"></i></a></li>
                @else
                    <li><span class="page-btn is-disabled"><i class="bi bi-chevron-right"></i></span></li>
                @endif
            </ul>
        </nav>
    </div>
@endif
