{{--
  Embedded Browse — browse-embedded.blade.php
  Adapted from Heratio ahg-display browse-embedded.blade.php
  Returns facets + results without full page layout (for AJAX/landing page embedding).
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
  $limit = (int) ($limit ?? request('limit', 10));
  if ($limit < 10) $limit = 10;
  if ($limit > 100) $limit = 100;

  $page       = (int) ($page ?? request('page', 1));
  $sort       = $sort ?? request('sort', 'date');
  $sortDir    = $sortDir ?? request('dir', 'desc');
  $viewMode   = $viewMode ?? request('view', 'card');
  $typeFilter = $typeFilter ?? request('type');
  $creatorFilter = $creatorFilter ?? request('creator');
  $placeFilter   = $placeFilter ?? request('place');
  $subjectFilter = $subjectFilter ?? request('subject');
  $genreFilter   = $genreFilter ?? request('genre');
  $levelFilter   = $levelFilter ?? request('level');
  $mediaFilter   = $mediaFilter ?? request('media');
  $repoFilter    = $repoFilter ?? request('repo');
  $hasDigital    = $hasDigital ?? request('hasDigital');
  $parentId      = $parentId ?? request('parent');

  $total      = $total ?? 0;
  $totalPages = $totalPages ?? 1;
  $parent     = $parent ?? null;
  $objects    = $objects ?? [];
  $types      = $types ?? [];
  $creators   = $creators ?? [];
  $places     = $places ?? [];
  $subjects   = $subjects ?? [];
  $genres     = $genres ?? [];
  $levels     = $levels ?? [];
  $mediaTypes = $mediaTypes ?? [];
  $repositories = $repositories ?? [];
  $showSidebar  = $showSidebar ?? true;

  $fp = [
      'type'       => $typeFilter,
      'parent'     => $parentId,
      'creator'    => $creatorFilter,
      'subject'    => $subjectFilter,
      'place'      => $placeFilter,
      'genre'      => $genreFilter,
      'level'      => $levelFilter,
      'media'      => $mediaFilter,
      'repo'       => $repoFilter,
      'hasDigital' => $hasDigital,
      'view'       => $viewMode,
      'limit'      => $limit,
      'sort'       => $sort,
      'dir'        => $sortDir,
  ];

  $typeConfig = [
      'archive' => ['icon' => 'fa-archive',  'color' => 'success', 'label' => 'Archive'],
      'museum'  => ['icon' => 'fa-landmark', 'color' => 'warning', 'label' => 'Museum'],
      'gallery' => ['icon' => 'fa-palette',  'color' => 'info',    'label' => 'Gallery'],
      'library' => ['icon' => 'fa-book',     'color' => 'primary', 'label' => 'Library'],
      'dam'     => ['icon' => 'fa-images',   'color' => 'danger',  'label' => 'Photo/DAM'],
  ];
  $limitOptions = [10, 25, 50, 100];
  $sortLabels = [
      'date'       => 'Date modified',
      'title'      => 'Title',
      'identifier' => 'Identifier',
      'refcode'    => 'Reference code',
      'startdate'  => 'Start date',
      'enddate'    => 'End date',
  ];

  if (!function_exists('buildEmbeddedUrl')) {
      function buildEmbeddedUrl($fp, $add = [], $remove = []) {
          $params = array_merge(array_filter($fp, function($v) { return $v !== null && $v !== ''; }), $add);
          foreach ($remove as $key) { unset($params[$key]); }
          unset($params['page']);
          return route('display.browse', $params);
      }
  }
@endphp

<div class="openric-browse-embedded">
  <div class="row">

    {{-- ========== FACETS SIDEBAR ========== --}}
    @if($showSidebar)
    <div class="col-lg-3 col-md-4">

      <div class="card mb-3">
        <div class="card-body py-2 text-white text-center bg-primary">
          <i class="fas fa-filter"></i> Filter by:
        </div>
      </div>

      {{-- Type Facet --}}
      @if(!empty($types))
      <div class="card mb-2">
        <div class="card-header py-2 bg-primary text-white cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#embFacetType">
          <strong>Type</strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse show" id="embFacetType">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ !$typeFilter ? 'active' : '' }}">
              <a href="{{ buildEmbeddedUrl($fp, [], ['type']) }}" class="text-decoration-none small {{ !$typeFilter ? 'text-white' : '' }}">All</a>
            </li>
            @foreach($types as $type)
              @php
                $tk = $type->object_type ?? '';
                $cfg = $typeConfig[$tk] ?? ['icon' => 'fa-question', 'color' => 'secondary', 'label' => ucfirst($tk)];
                $isActive = $typeFilter === $tk;
              @endphp
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ $isActive ? 'active' : '' }}">
                <a href="{{ $isActive ? buildEmbeddedUrl($fp, [], ['type']) : buildEmbeddedUrl($fp, ['type' => $tk]) }}" class="text-decoration-none small {{ $isActive ? 'text-white' : '' }}">
                  <i class="fas {{ $cfg['icon'] }} text-{{ $isActive ? 'white' : $cfg['color'] }}"></i>
                  {{ $cfg['label'] }}
                </a>
                <span class="badge bg-{{ $isActive ? 'light text-dark' : 'secondary' }} rounded-pill">{{ $type->count }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
      @endif

      {{-- Repository Facet --}}
      @if(!empty($repositories))
      <div class="card mb-2">
        <div class="card-header py-2 bg-primary text-white cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#embFacetRepo">
          <strong>Repository</strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetRepo">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ !$repoFilter ? 'active' : '' }}">
              <a href="{{ buildEmbeddedUrl($fp, [], ['repo']) }}" class="text-decoration-none small {{ !$repoFilter ? 'text-white' : '' }}">All</a>
            </li>
            @foreach($repositories as $repo)
              @php $isActive = $repoFilter == $repo->id; @endphp
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ $isActive ? 'active' : '' }}">
                <a href="{{ $isActive ? buildEmbeddedUrl($fp, [], ['repo']) : buildEmbeddedUrl($fp, ['repo' => $repo->id]) }}" class="text-decoration-none small text-truncate {{ $isActive ? 'text-white' : '' }}" style="max-width:180px">
                  {{ e($repo->name ?? '') }}
                </a>
                <span class="badge bg-{{ $isActive ? 'light text-dark' : 'secondary' }} rounded-pill">{{ $repo->count }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
      @endif

      {{-- Subject Facet --}}
      @if(!empty($subjects))
      <div class="card mb-2">
        <div class="card-header py-2 bg-primary text-white cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#embFacetSubject">
          <strong>Subject</strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetSubject">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ !$subjectFilter ? 'active' : '' }}">
              <a href="{{ buildEmbeddedUrl($fp, [], ['subject']) }}" class="text-decoration-none small {{ !$subjectFilter ? 'text-white' : '' }}">All</a>
            </li>
            @foreach($subjects as $subject)
              @php $isActive = $subjectFilter == $subject->id; @endphp
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ $isActive ? 'active' : '' }}">
                <a href="{{ $isActive ? buildEmbeddedUrl($fp, [], ['subject']) : buildEmbeddedUrl($fp, ['subject' => $subject->id]) }}" class="text-decoration-none small text-truncate {{ $isActive ? 'text-white' : '' }}" style="max-width:180px">
                  {{ e($subject->name ?? '') }}
                </a>
                <span class="badge bg-{{ $isActive ? 'light text-dark' : 'secondary' }} rounded-pill">{{ $subject->count }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
      @endif

      {{-- Level Facet --}}
      @if(!empty($levels))
      <div class="card mb-2">
        <div class="card-header py-2 bg-primary text-white cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#embFacetLevel">
          <strong>Level</strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetLevel">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ !$levelFilter ? 'active' : '' }}">
              <a href="{{ buildEmbeddedUrl($fp, [], ['level']) }}" class="text-decoration-none small {{ !$levelFilter ? 'text-white' : '' }}">All</a>
            </li>
            @foreach($levels as $level)
              @php $isActive = $levelFilter == $level->id; @endphp
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 {{ $isActive ? 'active' : '' }}">
                <a href="{{ $isActive ? buildEmbeddedUrl($fp, [], ['level']) : buildEmbeddedUrl($fp, ['level' => $level->id]) }}" class="text-decoration-none small {{ $isActive ? 'text-white' : '' }}">
                  {{ e($level->name ?? '') }}
                </a>
                <span class="badge bg-{{ $isActive ? 'light text-dark' : 'secondary' }} rounded-pill">{{ $level->count }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
      @endif

      <a href="{{ route('display.browse') }}" class="btn btn-outline-success btn-sm w-100 mt-2">
        <i class="fas fa-expand-arrows-alt me-1"></i> Full Browse Page
      </a>

    </div>
    @endif

    {{-- ========== RESULTS COLUMN ========== --}}
    <div class="{{ $showSidebar ? 'col-lg-9 col-md-8' : 'col-12' }}">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0 text-primary">
          <i class="fas fa-folder-open me-2"></i>
          Showing {{ number_format($total) }} results
        </h4>
        <a href="{{ route('display.browse') }}" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-search me-1"></i> Advanced Search
        </a>
      </div>

      {{-- Toolbar --}}
      <div class="d-flex flex-wrap gap-2 mb-3 small">
        <a href="{{ buildEmbeddedUrl($fp, ['view' => 'card']) }}" class="btn btn-sm {{ $viewMode === 'card' ? 'btn-outline-primary' : 'btn-outline-secondary' }}"><i class="fas fa-th-large"></i></a>
        <a href="{{ buildEmbeddedUrl($fp, ['view' => 'grid']) }}" class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-outline-primary' : 'btn-outline-secondary' }}"><i class="fas fa-th"></i></a>
        <a href="{{ buildEmbeddedUrl($fp, ['view' => 'table']) }}" class="btn btn-sm {{ $viewMode === 'table' ? 'btn-outline-primary' : 'btn-outline-secondary' }}"><i class="fas fa-list"></i></a>

        <div class="dropdown">
          <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">{{ $limit }}/page</button>
          <ul class="dropdown-menu">
            @foreach($limitOptions as $opt)
              <li><a class="dropdown-item {{ $limit == $opt ? 'active' : '' }}" href="{{ buildEmbeddedUrl($fp, ['limit' => $opt]) }}">{{ $opt }}</a></li>
            @endforeach
          </ul>
        </div>

        <div class="dropdown ms-auto">
          <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Sort: {{ $sortLabels[$sort] ?? 'Title' }}</button>
          <ul class="dropdown-menu">
            @foreach($sortLabels as $sortKey => $sortLabel)
              <li><a class="dropdown-item {{ $sort === $sortKey ? 'active' : '' }}" href="{{ buildEmbeddedUrl($fp, ['sort' => $sortKey]) }}">{{ $sortLabel }}</a></li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="mb-3 text-muted small">
        Results {{ min((($page - 1) * $limit) + 1, $total) }} to {{ min($page * $limit, $total) }} of {{ $total }}
      </div>

      {{-- Results --}}
      @if(empty($objects))
        <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-4x mb-3"></i><h4>No results</h4></div>
      @elseif($viewMode === 'grid')
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
          @foreach($objects as $obj)
            @php $cfg = $typeConfig[$obj->object_type ?? ''] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'Unknown']; $objUrl = '/' . ($obj->slug ?? ''); @endphp
            <div class="col">
              <div class="card h-100 shadow-sm">
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:120px;overflow:hidden;">
                  @if(!empty($obj->thumbnail))
                    <a href="{{ $objUrl }}"><img src="{{ $obj->thumbnail }}" alt="" class="img-fluid" style="max-height:120px;object-fit:cover;"></a>
                  @else
                    <a href="{{ $objUrl }}"><i class="fas {{ $cfg['icon'] }} fa-3x text-{{ $cfg['color'] }}"></i></a>
                  @endif
                </div>
                <div class="card-body p-2">
                  <a href="{{ $objUrl }}" class="text-primary text-decoration-none small d-block text-truncate">{{ e($obj->title ?? '[Untitled]') }}</a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @elseif($viewMode === 'table')
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead class="table-light"><tr><th style="width:60px"></th><th>Title</th><th style="width:100px">Level</th><th style="width:100px">Type</th></tr></thead>
            <tbody>
              @foreach($objects as $obj)
                @php $cfg = $typeConfig[$obj->object_type ?? ''] ?? ['icon' => 'fa-file', 'color' => 'secondary']; $objUrl = '/' . ($obj->slug ?? ''); @endphp
                <tr>
                  <td class="text-center">
                    @if(!empty($obj->thumbnail))
                      <img src="{{ $obj->thumbnail }}" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                    @else
                      <i class="fas {{ $cfg['icon'] }} fa-2x text-{{ $cfg['color'] }}"></i>
                    @endif
                  </td>
                  <td><a href="{{ $objUrl }}" class="text-primary text-decoration-none">{{ e($obj->title ?? '[Untitled]') }}</a>@if(!empty($obj->identifier))<br><small class="text-muted">{{ e($obj->identifier) }}</small>@endif</td>
                  <td><span class="badge bg-light text-dark">{{ e($obj->level_name ?? '-') }}</span></td>
                  <td><span class="badge bg-{{ $cfg['color'] }}">{{ ucfirst($obj->object_type ?? '?') }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        @foreach($objects as $obj)
          @php $cfg = $typeConfig[$obj->object_type ?? ''] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'Unknown']; $objUrl = '/' . ($obj->slug ?? ''); @endphp
          <div class="card mb-2 shadow-sm">
            <div class="row g-0">
              <div class="col-md-2 d-flex align-items-center justify-content-center p-2 bg-light">
                @if(!empty($obj->thumbnail))
                  <a href="{{ $objUrl }}"><img src="{{ $obj->thumbnail }}" alt="" class="img-fluid rounded" style="max-height:100px;object-fit:contain;"></a>
                @else
                  <a href="{{ $objUrl }}"><i class="fas {{ $cfg['icon'] }} fa-3x text-{{ $cfg['color'] }}"></i></a>
                @endif
              </div>
              <div class="col-md-10">
                <div class="card-body py-2">
                  <h6 class="card-title mb-1"><a href="{{ $objUrl }}" class="text-primary text-decoration-none">{{ e($obj->title ?? '[Untitled]') }}</a></h6>
                  <p class="card-text mb-1 small">
                    <span class="text-primary">{{ e($obj->identifier ?? '') }}</span>
                    @if(!empty($obj->level_name))<span class="mx-1">&middot;</span>{{ e($obj->level_name) }}@endif
                  </p>
                  @if(!empty($obj->scope_and_content))
                    <p class="card-text text-muted small mb-1">{{ Str::limit(strip_tags($obj->scope_and_content), 120) }}</p>
                  @endif
                  <span class="badge bg-{{ $cfg['color'] }}">{{ $cfg['label'] }}</span>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      @endif

      {{-- Pagination --}}
      @if($totalPages > 1)
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}"><a class="page-link" href="{{ buildEmbeddedUrl($fp, ['page' => $page - 1]) }}">Previous</a></li>
            @for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++)
              <li class="page-item {{ $i == $page ? 'active' : '' }}"><a class="page-link" href="{{ buildEmbeddedUrl($fp, ['page' => $i]) }}">{{ $i }}</a></li>
            @endfor
            <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}"><a class="page-link" href="{{ buildEmbeddedUrl($fp, ['page' => $page + 1]) }}">Next</a></li>
          </ul>
        </nav>
      @endif

    </div>
  </div>
</div>

<style>.cursor-pointer { cursor: pointer; }</style>
