{{--
  Admin feedback browse/listing page.

  Adapted from Heratio ahg-feedback::browse which renders a sidebar with status
  filter counts (All/Pending/Completed), sort controls (Name/Date up/down),
  a data table with type badges and status badges, and SimplePager pagination.
  OpenRiC replicates the full layout with Bootstrap 5 and adds search and export.
--}}
@extends('theme::layouts.1col')

@section('title', 'Feedback')
@section('body-class', 'browse feedback')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-comments me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Feedback Management</h1>
      @if($avgRating !== null)
        <span class="small text-muted">Average rating: {{ $avgRating }}/5</span>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row">
    {{-- Sidebar — mirrors Heratio's sidebar with filter counts --}}
    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-header fw-semibold" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-filter me-1"></i> Filter
        </div>
        <div class="list-group list-group-flush">
          <a href="{{ route('feedback.browse', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'all' ? 'active' : '' }}">
            All Feedback
            <span class="badge bg-secondary rounded-pill">{{ $totalCount }}</span>
          </a>
          <a href="{{ route('feedback.browse', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'pending' ? 'active' : '' }}">
            Pending
            <span class="badge bg-warning text-dark rounded-pill">{{ $pendingCount }}</span>
          </a>
          <a href="{{ route('feedback.browse', array_merge(request()->except('status', 'page'), ['status' => 'reviewed'])) }}"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'reviewed' ? 'active' : '' }}">
            Reviewed
            <span class="badge bg-info text-dark rounded-pill">{{ $reviewedCount }}</span>
          </a>
          <a href="{{ route('feedback.browse', array_merge(request()->except('status', 'page'), ['status' => 'completed'])) }}"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'completed' ? 'active' : '' }}">
            Completed
            <span class="badge bg-success rounded-pill">{{ $completedCount }}</span>
          </a>
        </div>
      </div>

      {{-- Search --}}
      <div class="card mb-3">
        <div class="card-header fw-semibold" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-search me-1"></i> Search
        </div>
        <div class="card-body">
          <form method="GET" action="{{ route('feedback.browse') }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Search feedback..."
                     value="{{ $search ?? '' }}">
              <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </div>
            @if($search)
              <a href="{{ route('feedback.browse', ['status' => $status, 'sort' => $sort]) }}"
                 class="btn btn-sm btn-link mt-1 p-0">Clear search</a>
            @endif
          </form>
        </div>
      </div>

      {{-- Actions --}}
      <div class="d-grid gap-2">
        <a href="{{ route('feedback.general') }}" class="btn btn-outline-primary">
          <i class="fas fa-plus me-1"></i> Add General Feedback
        </a>
        <a href="{{ route('feedback.export') }}" class="btn btn-outline-secondary">
          <i class="fas fa-download me-1"></i> Export CSV
        </a>
      </div>
    </div>

    {{-- Main content --}}
    <div class="col-md-9">
      {{-- Sort controls — mirrors Heratio's btn-group sort bar --}}
      <div class="d-flex flex-wrap gap-2 mb-3 justify-content-between align-items-center">
        <span class="text-muted">
          {{ $total }} result{{ $total !== 1 ? 's' : '' }}
          @if($status !== 'all') ({{ $status }}) @endif
        </span>
        <div class="btn-group btn-group-sm" role="group" aria-label="Sort options">
          <span class="btn btn-outline-secondary disabled">Sort by:</span>
          <a href="{{ route('feedback.browse', array_merge(request()->except('sort', 'page'), ['sort' => 'nameUp'])) }}"
             class="btn btn-outline-secondary {{ $sort === 'nameUp' ? 'active' : '' }}">
            Name <i class="fas fa-arrow-up"></i>
          </a>
          <a href="{{ route('feedback.browse', array_merge(request()->except('sort', 'page'), ['sort' => 'nameDown'])) }}"
             class="btn btn-outline-secondary {{ $sort === 'nameDown' ? 'active' : '' }}">
            Name <i class="fas fa-arrow-down"></i>
          </a>
          <a href="{{ route('feedback.browse', array_merge(request()->except('sort', 'page'), ['sort' => 'dateUp'])) }}"
             class="btn btn-outline-secondary {{ $sort === 'dateUp' ? 'active' : '' }}">
            Date <i class="fas fa-arrow-up"></i>
          </a>
          <a href="{{ route('feedback.browse', array_merge(request()->except('sort', 'page'), ['sort' => 'dateDown'])) }}"
             class="btn btn-outline-secondary {{ $sort === 'dateDown' ? 'active' : '' }}">
            Date <i class="fas fa-arrow-down"></i>
          </a>
        </div>
      </div>

      @if(count($results) > 0)
        <div class="table-responsive mb-3">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Message</th>
                <th>Contact</th>
                <th>Date</th>
                <th style="width:120px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($results as $i => $row)
                @php
                  $rowNum = ($page - 1) * $limit + $i + 1;

                  // Category badge mapping — mirrors Heratio's typeMap
                  $catMap = [
                      'general'    => ['label' => 'General',    'class' => 'bg-secondary'],
                      'bug'        => ['label' => 'Bug',        'class' => 'bg-danger'],
                      'feature'    => ['label' => 'Feature',    'class' => 'bg-info text-dark'],
                      'content'    => ['label' => 'Content',    'class' => 'bg-primary'],
                      'compliment' => ['label' => 'Compliment', 'class' => 'bg-success'],
                      'usability'  => ['label' => 'Usability',  'class' => 'bg-warning text-dark'],
                  ];
                  $catKey  = $row['category'] ?? 'general';
                  $catInfo = $catMap[$catKey] ?? $catMap['general'];

                  // Status badge — mirrors Heratio's pending/completed badge
                  $statusVal   = $row['status'] ?? 'pending';
                  $statusBadge = match ($statusVal) {
                      'completed' => '<span class="badge bg-success">Completed</span>',
                      'reviewed'  => '<span class="badge bg-info text-dark">Reviewed</span>',
                      'closed'    => '<span class="badge bg-dark">Closed</span>',
                      'new'       => '<span class="badge bg-primary">New</span>',
                      default     => '<span class="badge bg-warning text-dark">Pending</span>',
                  };
                @endphp
                <tr>
                  <td class="text-muted">{{ $rowNum }}</td>
                  <td>
                    <a href="{{ route('feedback.edit', $row['id']) }}">
                      {{ $row['subject'] ?: '[Untitled]' }}
                    </a>
                    @if(!empty($row['url']))
                      <br><a href="{{ url('/' . $row['url']) }}" class="small text-muted" title="View related record">
                        <i class="fas fa-link me-1"></i>{{ $row['url'] }}
                      </a>
                    @endif
                    <br>{!! $statusBadge !!}
                    @if(!empty($row['rating']))
                      <span class="ms-1 small text-warning">{{ str_repeat('★', (int) $row['rating']) }}{{ str_repeat('☆', 5 - (int) $row['rating']) }}</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge {{ $catInfo['class'] }}">{{ $catInfo['label'] }}</span>
                  </td>
                  <td title="{{ $row['message'] ?? '' }}">
                    {{ \Illuminate\Support\Str::limit($row['message'] ?? '', 60) }}
                  </td>
                  <td>
                    @if(!empty($row['feed_name']) || !empty($row['feed_surname']))
                      {{ $row['feed_name'] ?? '' }} {{ $row['feed_surname'] ?? '' }}
                    @elseif(!empty($row['user_name']))
                      {{ $row['user_name'] }}
                    @else
                      <span class="text-muted">Anonymous</span>
                    @endif
                    @if(!empty($row['feed_email']))
                      <br><small class="text-muted">{{ $row['feed_email'] }}</small>
                    @elseif(!empty($row['user_email']))
                      <br><small class="text-muted">{{ $row['user_email'] }}</small>
                    @endif
                  </td>
                  <td>
                    @if(!empty($row['created_at']))
                      {{ \Carbon\Carbon::parse($row['created_at'])->format('d M Y') }}
                    @endif
                    @if(!empty($row['completed_at']))
                      <br><small class="text-success">
                        <i class="fas fa-check me-1"></i>{{ \Carbon\Carbon::parse($row['completed_at'])->format('d M Y') }}
                      </small>
                    @endif
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="{{ route('feedback.view', $row['id']) }}" class="btn btn-sm btn-outline-secondary" title="View">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="{{ route('feedback.edit', $row['id']) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form method="POST" action="{{ route('feedback.destroy', $row['id']) }}"
                            onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @if($lastPage > 1)
          <nav aria-label="Feedback pagination">
            <ul class="pagination justify-content-center">
              <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('feedback.browse', array_merge(request()->except('page'), ['page' => $page - 1])) }}">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              @for($p = 1; $p <= $lastPage; $p++)
                @if($p === 1 || $p === $lastPage || abs($p - $page) <= 2)
                  <li class="page-item {{ $p === $page ? 'active' : '' }}">
                    <a class="page-link" href="{{ route('feedback.browse', array_merge(request()->except('page'), ['page' => $p])) }}">{{ $p }}</a>
                  </li>
                @elseif($p === 2 && $page > 4)
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                @elseif($p === $lastPage - 1 && $page < $lastPage - 3)
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
              @endfor
              <li class="page-item {{ $page >= $lastPage ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('feedback.browse', array_merge(request()->except('page'), ['page' => $page + 1])) }}">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        @endif
      @else
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>No feedback items found for the selected filter.
        </div>
      @endif
    </div>
  </div>
@endsection
