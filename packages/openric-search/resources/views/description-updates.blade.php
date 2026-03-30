@extends('theme::layouts.1col')

@section('title', 'Description updates')
@section('body-class', 'search description-updates')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-history me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">
        @if($total > 0)
          Showing {{ number_format($total) }} results
        @else
          Description updates
        @endif
      </h1>
    </div>
  </div>

  {{-- Filter form --}}
  <form action="{{ route('search.descriptionUpdates') }}" method="get" class="card mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label for="entityType" class="form-label">Entity type</label>
          <select name="entityType" id="entityType" class="form-select">
            @foreach($entityTypes as $value => $label)
              <option value="{{ $value }}" {{ $entityType === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label for="dateStart" class="form-label">Date start</label>
          <input type="date" name="dateStart" id="dateStart" class="form-control" value="{{ $dateStart }}">
        </div>
        <div class="col-md-2">
          <label for="dateEnd" class="form-label">Date end</label>
          <input type="date" name="dateEnd" id="dateEnd" class="form-control" value="{{ $dateEnd }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Date of</label>
          <div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="dateOf" id="dateOfCreated" value="created" {{ $dateOf === 'created' ? 'checked' : '' }}>
              <label class="form-check-label" for="dateOfCreated">Created</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="dateOf" id="dateOfUpdated" value="updated" {{ $dateOf === 'updated' ? 'checked' : '' }}>
              <label class="form-check-label" for="dateOfUpdated">Updated</label>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <label for="user" class="form-label">User</label>
          <select name="user" id="user" class="form-select">
            <option value="">All users</option>
            @foreach($users as $userId => $displayName)
              <option value="{{ $userId }}" {{ (string) $userName === (string) $userId ? 'selected' : '' }}>{{ $displayName }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-9 d-flex align-items-end gap-2">
          <button type="submit" class="btn atom-btn-outline-light">Search</button>
          <a href="{{ route('search.descriptionUpdates') }}" class="btn atom-btn-outline-light">Reset</a>
        </div>
      </div>
    </div>
  </form>

  @if($results !== null && $results->count() > 0)
    <div class="mb-2 text-muted small">Showing {{ number_format($total) }} result(s)</div>
    <table class="table table-bordered mb-0">
      <thead><tr><th>Title</th><th>Entity Type</th><th>User</th><th>Action</th><th>Date</th></tr></thead>
      <tbody>
        @foreach($results as $row)
          <tr>
            <td>{{ $row->title }}</td>
            <td><span class="badge bg-light text-dark">{{ $row->entity_type ?? '' }}</span></td>
            <td>{{ $row->username ?? '' }}</td>
            <td>{{ $row->action ?? '' }}</td>
            <td>{{ $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d H:i') : '' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @if($total > $limit)
      <nav class="mt-3"><ul class="pagination">
        @if($page > 1)<li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">Previous</a></li>@endif
        <li class="page-item disabled"><span class="page-link">Page {{ $page }} of {{ $lastPage }}</span></li>
        @if($page < $lastPage)<li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">Next</a></li>@endif
      </ul></nav>
    @endif
  @elseif($results !== null)
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> No description updates found matching the current filters.</div>
  @endif
@endsection
