{{-- Search box partial --}}
<form id="search-box" class="d-flex flex-grow-1 my-2" role="search" action="{{ route('search') }}">
  <div class="input-group flex-nowrap">
    <input id="search-box-input" class="form-control form-control-sm" type="search" name="q" autocomplete="off" value="{{ request('q') }}" placeholder="Search..." data-url="{{ route('search.suggest') }}" aria-label="Search">
    <button class="btn btn-sm atom-btn-secondary" type="submit"><i class="fas fa-search" aria-hidden="true"></i><span class="visually-hidden">Search</span></button>
  </div>
</form>
