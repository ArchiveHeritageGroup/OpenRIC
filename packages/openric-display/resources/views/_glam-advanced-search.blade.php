{{--
  Advanced Search Panel — _glam-advanced-search.blade.php
  Adapted from Heratio ahg-display _glam-advanced-search.blade.php
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
$params = request()->all();
$showAdvanced = ($params['showAdvanced'] ?? '') === '1';
$currentType = $params['type'] ?? '';

$levelsBySector = [];
try {
    $allLevels = \Illuminate\Support\Facades\DB::table('levels_of_description')
        ->orderBy('name')
        ->select('id', 'name')
        ->get()
        ->toArray();
    $levelsBySector[''] = $allLevels;
} catch (\Exception $e) {
    \Log::error("Levels query error: " . $e->getMessage());
    $levelsBySector[''] = [];
}

$repositories = [];
try {
    $repositories = \Illuminate\Support\Facades\DB::table('agents')
        ->where('type', 'repository')
        ->whereNotNull('title')
        ->where('title', '!=', '')
        ->orderBy('title')
        ->select('id', 'title as name')
        ->get()
        ->toArray();
} catch (\Exception $e) {
    \Log::error("Repository query error: " . $e->getMessage());
}

$currentLevels = $levelsBySector[''] ?? [];
@endphp

<div class="accordion mb-3" id="advancedSearchAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button{{ $showAdvanced ? '' : ' collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSearchPanel" aria-expanded="{{ $showAdvanced ? 'true' : 'false' }}">
        <i class="fas fa-sliders-h me-2"></i>{{ __('Advanced search options') }}
      </button>
    </h2>
    <div id="advancedSearchPanel" class="accordion-collapse collapse{{ $showAdvanced ? ' show' : '' }}">
      <div class="accordion-body">
        <form method="get" action="{{ route('display.browse') }}" id="advanced-search-form">

          <!-- Sector Quick Filter -->
          <div class="mb-4">
            <label class="form-label fw-bold"><i class="fas fa-layer-group me-1"></i>{{ __('Search in sector') }}</label>
            <div class="d-flex flex-wrap gap-2">
              <a href="{{ route('display.browse', ['showAdvanced' => '1']) }}" class="btn {{ empty($currentType) ? 'btn-secondary' : 'btn-outline-secondary' }}">
                <i class="fas fa-globe me-1"></i>{{ __('All') }}
              </a>
              <a href="{{ route('display.browse', ['type' => 'archive', 'showAdvanced' => '1']) }}" class="btn {{ $currentType === 'archive' ? 'btn-success' : 'btn-outline-success' }}">
                <i class="fas fa-archive me-1"></i>{{ __('Archive') }}
              </a>
              <a href="{{ route('display.browse', ['type' => 'library', 'showAdvanced' => '1']) }}" class="btn {{ $currentType === 'library' ? 'btn-info text-white' : 'btn-outline-info' }}">
                <i class="fas fa-book me-1"></i>{{ __('Library') }}
              </a>
              <a href="{{ route('display.browse', ['type' => 'museum', 'showAdvanced' => '1']) }}" class="btn {{ $currentType === 'museum' ? 'btn-warning' : 'btn-outline-warning' }}">
                <i class="fas fa-landmark me-1"></i>{{ __('Museum') }}
              </a>
              <a href="{{ route('display.browse', ['type' => 'gallery', 'showAdvanced' => '1']) }}" class="btn {{ $currentType === 'gallery' ? 'btn-danger' : 'btn-outline-danger' }}">
                <i class="fas fa-palette me-1"></i>{{ __('Gallery') }}
              </a>
              <a href="{{ route('display.browse', ['type' => 'dam', 'showAdvanced' => '1']) }}" class="btn {{ $currentType === 'dam' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fas fa-images me-1"></i>{{ __('Photos') }}
              </a>
            </div>
          </div>

          <!-- Nav Tabs -->
          <ul class="nav nav-tabs mb-3" id="advSearchTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic-search" type="button">{{ __('Basic') }}</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#content-search" type="button">{{ __('Content') }}</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#access-search" type="button">{{ __('Access Points') }}</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dates-search" type="button">{{ __('Dates') }}</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#filters-search" type="button">{{ __('Filters') }}</button></li>
          </ul>

          <div class="tab-content">
            <!-- Basic Tab -->
            <div class="tab-pane fade show active" id="basic-search">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Any field') }}</label>
                  <input type="text" name="query" class="form-control" value="{{ e($params['query'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Title') }}</label>
                  <input type="text" name="title" class="form-control" value="{{ e($params['title'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Identifier') }}</label>
                  <input type="text" name="identifier" class="form-control" value="{{ e($params['identifier'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Reference code') }}</label>
                  <input type="text" name="referenceCode" class="form-control" value="{{ e($params['referenceCode'] ?? '') }}">
                </div>
              </div>
            </div>

            <!-- Content Tab -->
            <div class="tab-pane fade" id="content-search">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Scope and content') }}</label>
                  <input type="text" name="scopeAndContent" class="form-control" value="{{ e($params['scopeAndContent'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Extent and medium') }}</label>
                  <input type="text" name="extentAndMedium" class="form-control" value="{{ e($params['extentAndMedium'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Archival history') }}</label>
                  <input type="text" name="archivalHistory" class="form-control" value="{{ e($params['archivalHistory'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Acquisition') }}</label>
                  <input type="text" name="acquisition" class="form-control" value="{{ e($params['acquisition'] ?? '') }}">
                </div>
              </div>
            </div>

            <!-- Access Points Tab -->
            <div class="tab-pane fade" id="access-search">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Creator') }}</label>
                  <input type="text" name="creatorSearch" class="form-control" value="{{ e($params['creatorSearch'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Subject') }}</label>
                  <input type="text" name="subjectSearch" class="form-control" value="{{ e($params['subjectSearch'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Place') }}</label>
                  <input type="text" name="placeSearch" class="form-control" value="{{ e($params['placeSearch'] ?? '') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">{{ __('Genre') }}</label>
                  <input type="text" name="genreSearch" class="form-control" value="{{ e($params['genreSearch'] ?? '') }}">
                </div>
              </div>
            </div>

            <!-- Dates Tab -->
            <div class="tab-pane fade" id="dates-search">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Date from') }}</label>
                  <input type="date" name="startDate" class="form-control" value="{{ e($params['startDate'] ?? '') }}">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Date to') }}</label>
                  <input type="date" name="endDate" class="form-control" value="{{ e($params['endDate'] ?? '') }}">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Date matching') }}</label>
                  <select name="rangeType" class="form-select">
                    <option value="inclusive" {{ ($params['rangeType'] ?? '') === 'inclusive' ? 'selected' : '' }}>{{ __('Overlapping') }}</option>
                    <option value="exact" {{ ($params['rangeType'] ?? '') === 'exact' ? 'selected' : '' }}>{{ __('Exact') }}</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Filters Tab -->
            <div class="tab-pane fade" id="filters-search">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Sector') }}</label>
                  <select name="type" class="form-select">
                    <option value="" {{ empty($currentType) ? 'selected' : '' }}>{{ __('All sectors') }}</option>
                    <option value="archive" {{ $currentType === 'archive' ? 'selected' : '' }}>{{ __('Archive') }}</option>
                    <option value="library" {{ $currentType === 'library' ? 'selected' : '' }}>{{ __('Library') }}</option>
                    <option value="museum" {{ $currentType === 'museum' ? 'selected' : '' }}>{{ __('Museum') }}</option>
                    <option value="gallery" {{ $currentType === 'gallery' ? 'selected' : '' }}>{{ __('Gallery') }}</option>
                    <option value="dam" {{ $currentType === 'dam' ? 'selected' : '' }}>{{ __('Photos') }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Level of description') }}</label>
                  <select name="level" class="form-select">
                    <option value="">{{ __('Any level') }}</option>
                    @foreach($currentLevels as $level)
                      <option value="{{ $level->id }}" {{ ($params['level'] ?? '') == $level->id ? 'selected' : '' }}>{{ e($level->name) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Repository') }}</label>
                  <select name="repo" class="form-select">
                    <option value="">{{ __('Any repository') }}</option>
                    @foreach($repositories as $repo)
                      <option value="{{ $repo->id }}" {{ ($params['repo'] ?? '') == $repo->id ? 'selected' : '' }}>{{ e($repo->name) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">{{ __('Digital objects') }}</label>
                  <select name="hasDigital" class="form-select">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1" {{ ($params['hasDigital'] ?? '') === '1' ? 'selected' : '' }}>{{ __('With digital objects') }}</option>
                    <option value="0" {{ ($params['hasDigital'] ?? '') === '0' ? 'selected' : '' }}>{{ __('Without digital objects') }}</option>
                  </select>
                </div>
                <div class="col-12">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="topLevel" id="topLevel-all" value="0" {{ ($params['topLevel'] ?? '0') === '0' ? 'checked' : '' }}>
                    <label class="form-check-label" for="topLevel-all">{{ __('All descriptions') }}</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="topLevel" id="topLevel-top" value="1" {{ ($params['topLevel'] ?? '') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="topLevel-top">{{ __('Top-level only') }}</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" name="showAdvanced" value="1">

          <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
            <a href="{{ route('display.browse') }}" class="btn btn-outline-secondary"><i class="fas fa-undo me-1"></i>{{ __('Reset') }}</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>{{ __('Search') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
