{{-- Search Enhancement Panel --}}
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
    // Silently fail if tables don't exist
}
@endphp

<div class="search-enhancement-panel mb-4">
  @if(!empty($templates))
  <div class="mb-3">
    <label class="form-label small text-muted">Quick Searches</label>
    <div class="d-flex flex-wrap gap-2">
      @foreach($templates as $template)
      @php $params = json_decode($template->search_params ?? '{}', true) ?: []; @endphp
      <a href="{{ route('search') . '?' . http_build_query($params) }}" class="btn btn-sm btn-outline-{{ e($template->color ?? 'secondary') }}">
        <i class="fa {{ e($template->icon ?? 'fa-search') }} me-1"></i>{{ e($template->name ?? '') }}
      </a>
      @endforeach
    </div>
  </div>
  @endif

  @if($isAuthenticated && !empty($savedSearches))
  <div class="mb-3">
    <label class="form-label small text-muted">Saved Searches</label>
    <div class="d-flex flex-wrap gap-2">
      @foreach(array_slice($savedSearches, 0, 5) as $saved)
      @php $params = json_decode($saved->search_params ?? '{}', true) ?: []; @endphp
      <a href="{{ route('search') . '?' . http_build_query($params) }}" class="badge bg-light text-dark text-decoration-none">{{ e($saved->name ?? '') }}</a>
      @endforeach
    </div>
  </div>
  @endif
</div>
