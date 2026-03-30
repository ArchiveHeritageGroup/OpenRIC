{{-- Advanced Search Enhancements --}}
@php
$isAuthenticated = \Illuminate\Support\Facades\Auth::check();
$savedSearches = [];
$templates = [];

try {
    if ($isAuthenticated) {
        $savedSearches = \Illuminate\Support\Facades\DB::table('saved_searches')
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
    $templates = \Illuminate\Support\Facades\DB::table('search_templates')
        ->where('is_active', true)
        ->where('is_featured', true)
        ->orderBy('sort_order')
        ->limit(6)
        ->get()
        ->toArray();
} catch (\Exception $e) {
    // Silently fail
}
@endphp

<div class="advanced-search-enhancements mt-3 pt-2 border-top">
  @if(!empty($templates))
  <div class="mb-2">
    <span class="text-muted small me-2"><i class="fa fa-bolt me-1"></i>Quick Searches</span>
    @foreach($templates as $template)
    @php $params = json_decode($template->search_params ?? '{}', true) ?: []; @endphp
    <a href="{{ route('search') . '?' . http_build_query($params) }}" class="btn btn-sm btn-outline-{{ e($template->color ?? 'secondary') }} py-0 px-2">
      <i class="fa {{ e($template->icon ?? 'fa-search') }} me-1"></i>{{ e($template->name ?? '') }}
    </a>
    @endforeach
  </div>
  @endif

  @if($isAuthenticated && !empty($savedSearches))
  <div class="d-flex align-items-center flex-wrap gap-2">
    <div class="dropdown">
      <button class="btn btn-sm atom-btn-outline-success dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown">
        <i class="fa fa-bookmark me-1"></i>Saved Searches ({{ count($savedSearches) }})
      </button>
      <ul class="dropdown-menu">
        @foreach($savedSearches as $saved)
        @php $params = json_decode($saved->search_params ?? '{}', true) ?: []; @endphp
        <li><a class="dropdown-item" href="{{ route('search') . '?' . http_build_query($params) }}">
          <i class="fa fa-search me-2 text-muted"></i>{{ e($saved->name ?? '') }}
        </a></li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif
</div>
