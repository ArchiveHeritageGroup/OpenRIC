{{--
  Browse Settings — display/browse-settings.blade.php
  Adapted from Heratio ahg-display display/browse-settings.blade.php
--}}
@extends('theme::layouts.1col')

@section('title', 'Browse Settings')
@section('body-class', 'admin display browse-settings')

@section('content')
<div id="main-column" role="main">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center">
      <i class="fas fa-3x fa-cog me-3 text-primary" aria-hidden="true"></i>
      <div>
        <h1 class="mb-0">Browse Settings</h1>
        <span class="small text-muted">Configure default browse behaviour and display preferences</span>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Browse Preferences</h5>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('display.browse.settings') }}">
        @csrf

        <h6 class="text-muted border-bottom pb-2 mb-3">Browse Interface</h6>
        <div class="mb-4">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="use_glam_browse" name="use_glam_browse"
                   value="1" {{ !empty($settings['use_glam_browse']) ? 'checked' : '' }}>
            <label class="form-check-label" for="use_glam_browse">
              <strong>Use enhanced browse as default</strong>
            </label>
          </div>
          <div class="form-text ms-4">
            When enabled, the enhanced browse interface with faceted search and type filtering is used by default.
          </div>
        </div>

        <h6 class="text-muted border-bottom pb-2 mb-3">Default Display Options</h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="default_view">Default View</label>
            <select name="default_view" id="default_view" class="form-select">
              <option value="list" {{ ($settings['default_view'] ?? 'list') === 'list' ? 'selected' : '' }}>List</option>
              <option value="card" {{ ($settings['default_view'] ?? '') === 'card' ? 'selected' : '' }}>Cards</option>
              <option value="table" {{ ($settings['default_view'] ?? '') === 'table' ? 'selected' : '' }}>Table</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="items_per_page">Items Per Page</label>
            <select name="items_per_page" id="items_per_page" class="form-select">
              @foreach([10, 20, 30, 50, 100] as $n)
                <option value="{{ $n }}" {{ ($settings['items_per_page'] ?? 30) == $n ? 'selected' : '' }}>{{ $n }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <h6 class="text-muted border-bottom pb-2 mb-3">Default Sorting</h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="default_sort_field">Sort By</label>
            <select name="default_sort_field" id="default_sort_field" class="form-select">
              <option value="updated_at" {{ ($settings['default_sort_field'] ?? 'updated_at') === 'updated_at' ? 'selected' : '' }}>Last Updated</option>
              <option value="title" {{ ($settings['default_sort_field'] ?? '') === 'title' ? 'selected' : '' }}>Title</option>
              <option value="identifier" {{ ($settings['default_sort_field'] ?? '') === 'identifier' ? 'selected' : '' }}>Identifier</option>
              <option value="date" {{ ($settings['default_sort_field'] ?? '') === 'date' ? 'selected' : '' }}>Date Created</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="default_sort_direction">Direction</label>
            <select name="default_sort_direction" id="default_sort_direction" class="form-select">
              <option value="desc" {{ ($settings['default_sort_direction'] ?? 'desc') === 'desc' ? 'selected' : '' }}>Descending (newest first)</option>
              <option value="asc" {{ ($settings['default_sort_direction'] ?? '') === 'asc' ? 'selected' : '' }}>Ascending (oldest first)</option>
            </select>
          </div>
        </div>

        <h6 class="text-muted border-bottom pb-2 mb-3">Additional Options</h6>
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="show_facets" name="show_facets"
                   value="1" {{ ($settings['show_facets'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="show_facets">Show filter sidebar (facets)</label>
          </div>
        </div>
        <div class="mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember_filters" name="remember_filters"
                   value="1" {{ ($settings['remember_filters'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="remember_filters">Remember my last used filters</label>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-check-lg me-1"></i> Save Settings
          </button>
          <a href="{{ route('display.browse') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="button" class="btn btn-outline-danger ms-auto" id="reset-settings">
            <i class="fas fa-undo me-1"></i> Reset to Defaults
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('reset-settings').addEventListener('click', function() {
  if (confirm('Reset all browse settings to defaults?')) {
    fetch('{{ route("display.reset.settings") }}', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { if (data.success) window.location.reload(); else alert('Failed to reset settings'); })
    .catch(function() { alert('Failed to reset settings'); });
  }
});
</script>
@endpush
@endsection
