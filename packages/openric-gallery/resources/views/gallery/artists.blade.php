@extends('theme::layouts.1col')

@section('title', 'Gallery artists')
@section('body-class', 'browse gallery-artists')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-users me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">
        @if($pager->total)
          Showing {{ number_format($pager->total) }} results
        @else
          No results found
        @endif
      </h1>
      <span class="small text-muted">Gallery artists</span>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <form method="get" action="{{ route('gallery.artists') }}" class="d-flex flex-grow-1" role="search" aria-label="Gallery artist">
      @foreach(request()->except(['subquery', 'page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
      @endforeach
      <div class="input-group input-group-sm">
        <input type="text" class="form-control" name="subquery" value="{{ request('subquery') }}" placeholder="Search artists" aria-label="Search artists">
        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
      </div>
    </form>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
          Sort: {{ $sortOptions[request('sort', 'alphabetic')] ?? 'Name' }}
        </button>
        <ul class="dropdown-menu">
          @foreach($sortOptions as $key => $label)
            <li>
              <a class="dropdown-item {{ request('sort', 'alphabetic') === $key ? 'active' : '' }}"
                 href="{{ route('gallery.artists', array_merge(request()->except('sort', 'page'), ['sort' => $key])) }}">
                {{ $label }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>

      @auth
        <a href="{{ route('gallery.artists.create') }}" class="btn btn-sm btn-outline-success">
          <i class="fas fa-plus me-1"></i> Add new
        </a>
      @endauth
    </div>
  </div>

  @if($pager->total)
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-striped mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Nationality</th>
            <th>Type</th>
            <th>Medium / Specialty</th>
            <th>Movement / Style</th>
            <th>Active period</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pager->results as $artist)
            <tr>
              <td>
                <a href="{{ route('gallery.artists.show', $artist['id']) }}">
                  {{ $artist['display_name'] ?: '[Unknown]' }}
                </a>
              </td>
              <td>{{ $artist['nationality'] ?? '' }}</td>
              <td>{{ $artist['artist_type'] ?? '' }}</td>
              <td>{{ $artist['medium_specialty'] ?? '' }}</td>
              <td>{{ $artist['movement_style'] ?? '' }}</td>
              <td>{{ $artist['active_period'] ?? '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  @if($pager->lastPage > 1)
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        @if($pager->page > 1)
          <li class="page-item"><a class="page-link" href="{{ route('gallery.artists', array_merge(request()->all(), ['page' => $pager->page - 1])) }}">Previous</a></li>
        @endif
        @for($i = max(1, $pager->page - 3); $i <= min($pager->lastPage, $pager->page + 3); $i++)
          <li class="page-item {{ $i == $pager->page ? 'active' : '' }}"><a class="page-link" href="{{ route('gallery.artists', array_merge(request()->all(), ['page' => $i])) }}">{{ $i }}</a></li>
        @endfor
        @if($pager->page < $pager->lastPage)
          <li class="page-item"><a class="page-link" href="{{ route('gallery.artists', array_merge(request()->all(), ['page' => $pager->page + 1])) }}">Next</a></li>
        @endif
      </ul>
    </nav>
  @endif
@endsection
