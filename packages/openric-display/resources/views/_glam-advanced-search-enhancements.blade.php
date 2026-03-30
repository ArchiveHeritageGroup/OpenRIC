{{--
  Advanced Search Enhancements — _glam-advanced-search-enhancements.blade.php
  Adapted from Heratio ahg-display _glam-advanced-search-enhancements.blade.php
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
$isAuthenticated = auth()->check();
$isAdmin = $isAuthenticated && (auth()->user()->is_admin ?? false);

$savedSearches = [];
try {
    if ($isAuthenticated) {
        $savedSearches = \Illuminate\Support\Facades\DB::table('saved_searches')
            ->where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
} catch (\Exception $e) {
    // Silently fail
}
@endphp

<div class="advanced-search-enhancements mt-3 pt-2 border-top">
  @if($isAuthenticated)
  <div class="d-flex align-items-center flex-wrap gap-2">
    @if(!empty($savedSearches))
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-primary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bookmark me-1"></i>{{ __('Saved Searches') }} ({{ count($savedSearches) }})
      </button>
      <ul class="dropdown-menu">
        @foreach($savedSearches as $saved)
        @php $searchParams = json_decode($saved->search_params, true) ?: []; @endphp
        <li><a class="dropdown-item" href="{{ route('display.browse') }}?{{ http_build_query($searchParams) }}">
          <i class="fas fa-search me-2 text-muted"></i>{{ e($saved->name) }}
        </a></li>
        @endforeach
      </ul>
    </div>
    @endif

    @if(!empty(request()->all()))
    <button type="button" class="btn btn-sm btn-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#saveSearchModal">
      <i class="fas fa-bookmark me-1"></i>{{ __('Save Search') }}
    </button>
    @endif
  </div>
  @endif
</div>

@if($isAuthenticated)
<div class="modal fade" id="saveSearchModal" tabindex="-1" aria-labelledby="saveSearchModalLabel" aria-modal="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="saveSearchModalLabel">{{ __('Save This Search') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">{{ __('Name') }} *</label>
          <input type="text" id="save-search-name" class="form-control" required>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-public">
          <label class="form-check-label" for="save-search-public">
            <i class="fas fa-link me-1"></i>{{ __('Make public (shareable link)') }}
          </label>
        </div>
        @if($isAdmin)
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-global">
          <label class="form-check-label" for="save-search-global">
            <i class="fas fa-globe me-1"></i>{{ __('Global (visible to all users)') }}
          </label>
        </div>
        @endif
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="save-search-notify">
          <label class="form-check-label" for="save-search-notify">{{ __('Notify me of new results') }}</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-primary" id="saveSearchBtn">{{ __('Save') }}</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('saveSearchBtn').addEventListener('click', function() {
    var name = document.getElementById('save-search-name').value;
    if (!name) { alert('Please enter a name'); return; }
    var params = window.location.search.substring(1);
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var data = new FormData();
    data.append('_token', csrfToken);
    data.append('name', name);
    data.append('search_params', params);
    data.append('is_public', document.getElementById('save-search-public').checked ? 1 : 0);
    data.append('notify', document.getElementById('save-search-notify').checked ? 1 : 0);
    if (document.getElementById('save-search-global')) {
        data.append('is_global', document.getElementById('save-search-global').checked ? 1 : 0);
    }

    fetch('{{ route("display.save.settings") }}', { method: 'POST', body: data })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('saveSearchModal'));
            if (modal) modal.hide();
            alert('Search saved!');
            location.reload();
        } else { alert(result.error || 'Error saving'); }
    })
    .catch(function(e) { alert('Error: ' + e.message); });
});
</script>
@endpush
@endif
