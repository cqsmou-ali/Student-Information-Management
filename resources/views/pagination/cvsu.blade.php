@if ($paginator->total() > 0)
<nav class="pager" aria-label="Pagination">
  <span>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
  @if ($paginator->hasPages())
  <span>
    <a class="btn btn-light btn-sm {{ $paginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev">&larr; Previous</a>
    <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    <a class="btn btn-light btn-sm {{ $paginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next">Next &rarr;</a>
  </span>
  @endif
</nav>
@endif
