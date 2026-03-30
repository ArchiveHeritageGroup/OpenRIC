@extends('theme::layouts.1col')

@section('title', 'Rights holders')
@section('body-class', 'browse rightsholder')

@section('content')
  <h1>Browse rights holders</h1>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="subquery" class="form-control form-control-sm" placeholder="Search rights holders..."
             value="{{ $filters['subquery'] ?? '' }}" aria-label="Search rights holders">
      <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
    </form>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      @php $activeSort = $filters['sort'] ?? 'alphabetic'; @endphp
      <div class="dropdown d-inline-block">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
          Sort by: {{ $sortOptions[$activeSort] ?? 'Name' }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          @foreach($sortOptions as $key => $label)
            <li><a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}" class="dropdown-item {{ $activeSort === $key ? 'active' : '' }}">{{ $label }}</a></li>
          @endforeach
        </ul>
      </div>

      @php
        $currentDir = $filters['sortDir'] ?? ($activeSort === 'lastUpdated' ? 'desc' : 'asc');
      @endphp
      <div class="dropdown d-inline-block">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
          {{ $currentDir === 'desc' ? 'Descending' : 'Ascending' }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a href="{{ request()->fullUrlWithQuery(['sortDir' => 'asc', 'page' => null]) }}" class="dropdown-item {{ $currentDir === 'asc' ? 'active' : '' }}">Ascending</a></li>
          <li><a href="{{ request()->fullUrlWithQuery(['sortDir' => 'desc', 'page' => null]) }}" class="dropdown-item {{ $currentDir === 'desc' ? 'active' : '' }}">Descending</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="table-responsive mb-3">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Identifier</th>
          <th>Updated</th>
        </tr>
      </thead>
      <tbody>
        @forelse($hits as $doc)
          <tr>
            <td><a href="{{ route('rights.holders.show', $doc['id']) }}">{{ $doc['name'] ?: '[Untitled]' }}</a></td>
            <td>{{ $doc['description_identifier'] ?? '' }}</td>
            <td>
              @if(!empty($doc['updated_at']))
                {{ \Carbon\Carbon::parse($doc['updated_at'])->format('j F Y g:i A') }}
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-muted text-center py-3">No rights holders found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($total > $limit)
    <nav aria-label="Pagination">
      <ul class="pagination justify-content-center">
        @for($i = 1; $i <= ceil($total / $limit); $i++)
          <li class="page-item {{ $page === $i ? 'active' : '' }}">
            <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
          </li>
        @endfor
      </ul>
    </nav>
  @endif

  @auth
    <section class="actions mb-3">
      <a class="btn btn-outline-primary" href="{{ route('rights.holders.create') }}">Add new</a>
    </section>
  @endauth
@endsection
