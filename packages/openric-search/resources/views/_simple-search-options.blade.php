{{-- Simple Search Options Dropdown --}}
<div class="dropdown-menu search-options-dropdown" id="search-options-dropdown">
  <div class="form-check px-3 py-2">
    <input class="form-check-input" type="radio" name="searchType" id="globalSearch" value="global" checked>
    <label class="form-check-label" for="globalSearch"><i class="fa fa-globe me-1"></i>Global search</label>
  </div>
  <a class="dropdown-item" href="{{ route('search.advanced') }}"><i class="fa fa-sliders-h me-2"></i>Advanced search</a>
  <div class="dropdown-divider"></div>
</div>

<style>
.search-options-dropdown { min-width: 280px; max-height: 70vh; overflow-y: auto; }
.search-options-dropdown .dropdown-header { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; padding-top: 0.75rem; }
.search-options-dropdown .dropdown-item { padding: 0.4rem 1rem; font-size: 0.9rem; }
</style>
