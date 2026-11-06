@props(['items'])
<div class="pagination">
    <span>{{ $items->total() ? $items->firstItem() . '–' . $items->lastItem() : '0' }} dari {{ $items->total() }}
        hasil</span>
    <div class="pagination-actions">
        @if ($items->onFirstPage())
        <span class="button secondary disabled">Sebelumnya</span>@else<a class="button secondary"
                href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
        @endif
        <span>
            {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
        @if ($items->hasMorePages())
        <a class="button secondary" href="{{ $items->nextPageUrl() }}">Berikutnya</a>@else<span
                class="button secondary disabled">Berikutnya</span>
        @endif
    </div>
</div>
