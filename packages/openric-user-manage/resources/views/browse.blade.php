@extends('theme::layouts.1col')

@section('title', 'Users')
@section('body-class', 'browse user')

@section('content')
  <h1>List users</h1>

  <div class="d-inline-block mb-3">
    <form method="get" action="{{ route('user.browse') }}" class="d-flex" role="search" aria-label="User">
      @foreach(request()->except(['subquery', 'search', 'page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
      @endforeach
      <div class="input-group input-group-sm">
        <input type="text" class="form-control" name="subquery" value="{{ request('subquery', request('search')) }}" placeholder="Search users" aria-label="Search users">
        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
      </div>
    </form>
  </div>

  <nav>
    <ul class="nav nav-pills mb-3 d-flex gap-2">
      <li class="nav-item">
        <a class="btn btn-outline-secondary text-wrap {{ request('filter', 'onlyActive') !== 'onlyInactive' ? 'active' : '' }}" href="?filter=onlyActive" {{ request('filter', 'onlyActive') !== 'onlyInactive' ? 'aria-current=page' : '' }}>Show active only</a>
      </li>
      <li class="nav-item">
        <a class="btn btn-outline-secondary text-wrap {{ request('filter') === 'onlyInactive' ? 'active' : '' }}" href="?filter=onlyInactive" {{ request('filter') === 'onlyInactive' ? 'aria-current=page' : '' }}>Show inactive only</a>
      </li>
    </ul>
  </nav>

  @if($total > 0)
    <div class="table-responsive mb-3">
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th>User name</th>
            <th>Email</th>
            <th>User groups</th>
          </tr>
        </thead>
        <tbody>
          @foreach($users as $doc)
            <tr>
              <td>
                <a href="{{ route('user.show', $doc['slug'] ?? $doc['id']) }}">
                  {{ $doc['username'] ?? $doc['name'] ?? '[Untitled]' }}
                </a>
                @if(!($doc['active'] ?? true))
                  (inactive)
                @endif
                @if(isset($currentUserId) && ($doc['id'] ?? null) == $currentUserId)
                  (you)
                @endif
              </td>
              <td>{{ $doc['email'] ?? '' }}</td>
              <td>
                @if(!empty($doc['groups']))
                  @if(is_string($doc['groups']))
                    <ul class="mb-0">
                      @foreach(explode(', ', $doc['groups']) as $group)
                        <li>{{ $group }}</li>
                      @endforeach
                    </ul>
                  @elseif(is_array($doc['groups']))
                    <ul class="mb-0">
                      @foreach($doc['groups'] as $group)
                        <li>{{ is_array($group) ? ($group['name'] ?? '') : (is_object($group) ? ($group->name ?? '') : $group) }}</li>
                      @endforeach
                    </ul>
                  @endif
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="text-center py-4 text-muted">No users found.</div>
  @endif

  @if(($pages ?? 1) > 1)
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        @if($page > 1)
          <li class="page-item"><a class="page-link" href="{{ route('user.browse', array_merge(request()->all(), ['page' => $page - 1])) }}">Previous</a></li>
        @endif
        @for($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++)
          <li class="page-item {{ $i == $page ? 'active' : '' }}"><a class="page-link" href="{{ route('user.browse', array_merge(request()->all(), ['page' => $i])) }}">{{ $i }}</a></li>
        @endfor
        @if($page < $pages)
          <li class="page-item"><a class="page-link" href="{{ route('user.browse', array_merge(request()->all(), ['page' => $page + 1])) }}">Next</a></li>
        @endif
      </ul>
    </nav>
  @endif

  <section class="actions mb-3">
    <a class="btn btn-outline-success" href="{{ route('user.add') }}">Add new</a>
  </section>
@endsection
