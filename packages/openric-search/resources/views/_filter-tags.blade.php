{{-- Filter tag collection --}}
@if(!empty($activeFilters))
  <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small me-1">Filters:</span>
    @foreach($activeFilters as $filter)
      @include('search::_filter-tag', ['filter' => $filter])
    @endforeach
    <a href="{{ route('search', ['q' => $query ?? '']) }}" class="btn btn-sm atom-btn-outline-danger">Clear all filters</a>
  </div>
@endif
