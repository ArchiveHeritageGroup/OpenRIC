@extends('theme::layouts.1col')

@section('title', 'Gallery artworks')
@section('body-class', 'browse gallery')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-palette me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">
        @if($pager->total)
          Showing {{ number_format($pager->total) }} results
        @else
          No results found
        @endif
      </h1>
      <span class="small text-muted">Gallery artworks</span>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3">
    {{-- Inline search --}}
    <form method="get" action="{{ route('gallery.artwork.browse') }}" class="d-flex flex-grow-1" role="search" aria-label="Gallery artwork">
      @foreach(request()->except(['subquery', 'page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
      @endforeach
      <div class="input-group input-group-sm">
        <input type="text" class="form-control" name="subquery" value="{{ request('subquery') }}" placeholder="Search gallery artworks" aria-label="Search gallery artworks">
        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
      </div>
    </form>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      @if($repositories->isNotEmpty())
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            {{ $selectedRepository ? ($repositoryNames[$selectedRepository] ?? 'Repository') : 'Repository' }}
          </button>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item {{ !$selectedRepository ? 'active' : '' }}"
                 href="{{ route('gallery.artwork.browse', array_merge(request()->except('repository', 'page'), [])) }}">
                All repositories
              </a>
            </li>
            @foreach($repositories as $repo)
              <li>
                <a class="dropdown-item {{ $selectedRepository == $repo->id ? 'active' : '' }}"
                   href="{{ route('gallery.artwork.browse', array_merge(request()->except('page'), ['repository' => $repo->id])) }}">
                  {{ $repo->name }}
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Sort picker --}}
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
          Sort: {{ $sortOptions[request('sort', 'alphabetic')] ?? 'Title' }}
        </button>
        <ul class="dropdown-menu">
          @foreach($sortOptions as $key => $label)
            <li>
              <a class="dropdown-item {{ request('sort', 'alphabetic') === $key ? 'active' : '' }}"
                 href="{{ route('gallery.artwork.browse', array_merge(request()->except('sort', 'page'), ['sort' => $key])) }}">
                {{ $label }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>

      @auth
        <a href="{{ route('gallery.artwork.create') }}" class="btn btn-sm btn-outline-success">
          <i class="fas fa-plus me-1"></i> Add new
        </a>
      @endauth
    </div>
  </div>

  @if($pager->total)
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 mb-3">
      @foreach($pager->results as $doc)
        @php
          $thumbUrl = null;
          $is3D = false;
          if (!empty($doc['thumbnail'])) {
              $thumbUrl = '/uploads/' . $doc['thumbnail']->path . '/' . $doc['thumbnail']->name;
          } elseif (!empty($doc['master_path']) && !empty($doc['master_name'])) {
              $ext = strtolower(pathinfo($doc['master_name'], PATHINFO_EXTENSION));
              if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                  $thumbUrl = '/uploads/' . $doc['master_path'] . '/' . $doc['master_name'];
              }
              $is3D = in_array($ext, ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae']);
          }
        @endphp
        <div class="col">
          <div class="card h-100 shadow-sm">
            <a href="{{ route('gallery.artwork.show', $doc['slug']) }}"@if(!$thumbUrl) class="text-decoration-none"@endif>
              @if($thumbUrl)
                <img src="{{ e($thumbUrl) }}"
                     class="card-img-top"
                     alt="{{ e($doc['name'] ?? '') }}"
                     style="height: 180px; object-fit: cover;">
              @elseif($is3D)
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 180px;">
                  <i class="fas fa-cube fa-4x text-primary"></i>
                </div>
              @else
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 180px;">
                  <i class="fas fa-image fa-4x text-muted"></i>
                </div>
              @endif
            </a>
            <div class="card-body p-2">
              <h6 class="card-title text-truncate mb-1">
                <a href="{{ route('gallery.artwork.show', $doc['slug']) }}" class="text-decoration-none">
                  {{ $doc['name'] ?: '[Untitled]' }}
                </a>
              </h6>
              @if(!empty($doc['creator_identity']))
                <p class="card-text text-muted small mb-1">
                  <i class="fas fa-user me-1"></i>{{ $doc['creator_identity'] }}
                </p>
              @endif
              @if(!empty($doc['work_type']) || !empty($doc['materials']))
                <p class="card-text text-muted small mb-1">
                  @if(!empty($doc['work_type']))
                    <span class="badge bg-secondary me-1">{{ $doc['work_type'] }}</span>
                  @endif
                  @if(!empty($doc['materials']))
                    {{ $doc['materials'] }}
                  @endif
                </p>
              @endif
              @if(!empty($doc['creation_date_display']))
                <p class="card-text text-muted small mb-0">
                  <i class="fas fa-calendar me-1"></i>{{ $doc['creation_date_display'] }}
                </p>
              @endif
            </div>
            @if(!empty($doc['identifier']))
              <div class="card-footer bg-transparent p-2">
                <small class="text-muted">{{ $doc['identifier'] }}</small>
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Pagination --}}
  @if($pager->lastPage > 1)
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        @if($pager->page > 1)
          <li class="page-item">
            <a class="page-link" href="{{ route('gallery.artwork.browse', array_merge(request()->all(), ['page' => $pager->page - 1])) }}">Previous</a>
          </li>
        @endif
        @for($i = max(1, $pager->page - 3); $i <= min($pager->lastPage, $pager->page + 3); $i++)
          <li class="page-item {{ $i == $pager->page ? 'active' : '' }}">
            <a class="page-link" href="{{ route('gallery.artwork.browse', array_merge(request()->all(), ['page' => $i])) }}">{{ $i }}</a>
          </li>
        @endfor
        @if($pager->page < $pager->lastPage)
          <li class="page-item">
            <a class="page-link" href="{{ route('gallery.artwork.browse', array_merge(request()->all(), ['page' => $pager->page + 1])) }}">Next</a>
          </li>
        @endif
      </ul>
    </nav>
  @endif
@endsection
