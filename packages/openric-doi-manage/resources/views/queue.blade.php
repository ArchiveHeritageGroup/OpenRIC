@extends('theme::layouts.1col')

@section('title', 'DOI Queue')
@section('body-class', 'admin doi queue')

@section('content')
  @if(!($tablesExist ?? false))
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle me-2"></i>
      The DOI queue table (<code>doi_queue</code>) has not been created yet. Please run the database migration.
    </div>
  @else
    <div class="multiline-header d-flex align-items-center mb-3">
      <i class="fas fa-3x fa-tasks me-3" aria-hidden="true"></i>
      <div class="d-flex flex-column flex-grow-1">
        <h1 class="mb-0">
          @if($queueItems->total())
            Showing {{ number_format($queueItems->total()) }} results
          @else
            No queue items
          @endif
        </h1>
        <span class="small text-muted">DOI Queue</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('doi.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i> Dashboard
        </a>
        <a href="{{ route('doi.batch-mint') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-plus me-1"></i> Batch Mint
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    {{-- Status summary cards --}}
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card text-center border-warning">
          <div class="card-body py-2">
            <div class="fs-3 fw-bold text-warning">{{ number_format($statusCounts['pending']) }}</div>
            <div class="small text-muted">Pending</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center border-primary">
          <div class="card-body py-2">
            <div class="fs-3 fw-bold text-primary">{{ number_format($statusCounts['processing']) }}</div>
            <div class="small text-muted">Processing</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center border-danger">
          <div class="card-body py-2">
            <div class="fs-3 fw-bold text-danger">{{ number_format($statusCounts['failed']) }}</div>
            <div class="small text-muted">Failed</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center border-success">
          <div class="card-body py-2">
            <div class="fs-3 fw-bold text-success">{{ number_format($statusCounts['completed']) }}</div>
            <div class="small text-muted">Completed</div>
          </div>
        </div>
      </div>
    </div>

    {{-- CLI hint --}}
    <div class="alert alert-info">
      <i class="fas fa-terminal me-2"></i>
      <strong>Process queue via CLI:</strong>
      <code class="ms-2">php artisan doi:process-queue</code>
    </div>

    {{-- Status filter tabs --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
      <a href="{{ route('doi.queue') }}"
         class="btn btn-sm {{ $currentStatus === '' ? 'btn-primary' : 'btn-outline-primary' }}">
        All
      </a>
      <a href="{{ route('doi.queue', ['status' => 'pending']) }}"
         class="btn btn-sm {{ $currentStatus === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
        Pending
      </a>
      <a href="{{ route('doi.queue', ['status' => 'processing']) }}"
         class="btn btn-sm {{ $currentStatus === 'processing' ? 'btn-primary' : 'btn-outline-primary' }}">
        Processing
      </a>
      <a href="{{ route('doi.queue', ['status' => 'failed']) }}"
         class="btn btn-sm {{ $currentStatus === 'failed' ? 'btn-danger' : 'btn-outline-danger' }}">
        Failed
      </a>
      <a href="{{ route('doi.queue', ['status' => 'completed']) }}"
         class="btn btn-sm {{ $currentStatus === 'completed' ? 'btn-success' : 'btn-outline-success' }}">
        Completed
      </a>
    </div>

    @if($queueItems->total())
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-striped mb-0">
          <thead>
            <tr>
              <th>Entity IRI</th>
              <th>Action</th>
              <th>Status</th>
              <th>Attempts</th>
              <th>Scheduled</th>
              <th>Error</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($queueItems as $item)
              <tr>
                <td>
                  <code class="small" title="{{ $item->entity_iri }}">
                    {{ \Illuminate\Support\Str::limit($item->entity_iri, 60) }}
                  </code>
                </td>
                <td>
                  @if($item->action === 'mint')
                    <span class="badge bg-primary">Mint</span>
                  @elseif($item->action === 'update')
                    <span class="badge bg-info">Update</span>
                  @elseif($item->action === 'delete')
                    <span class="badge bg-danger">Delete</span>
                  @else
                    <span class="badge bg-secondary">{{ $item->action }}</span>
                  @endif
                </td>
                <td>
                  @if($item->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                  @elseif($item->status === 'processing')
                    <span class="badge bg-primary">Processing</span>
                  @elseif($item->status === 'completed')
                    <span class="badge bg-success">Completed</span>
                  @elseif($item->status === 'failed')
                    <span class="badge bg-danger">Failed</span>
                  @else
                    <span class="badge bg-secondary">{{ $item->status }}</span>
                  @endif
                </td>
                <td>{{ $item->attempts }}/{{ config('openric-doi.max_attempts', 3) }}</td>
                <td>{{ $item->scheduled_at ? \Carbon\Carbon::parse($item->scheduled_at)->format('Y-m-d H:i') : '-' }}</td>
                <td>
                  @if($item->error_message)
                    <small class="text-danger" title="{{ $item->error_message }}">
                      {{ \Illuminate\Support\Str::limit($item->error_message, 50) }}
                    </small>
                  @else
                    -
                  @endif
                </td>
                <td class="text-end">
                  @if($item->status === 'failed')
                    <a href="{{ route('doi.queue', ['retry' => $item->id]) }}" class="btn btn-sm btn-outline-warning" title="Retry">
                      <i class="fas fa-redo"></i>
                    </a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{ $queueItems->appends(request()->except('page'))->links() }}
    @else
      <div class="text-center text-muted py-4">
        <i class="fas fa-tasks fa-3x mb-3"></i>
        <p>The queue is empty.</p>
        <a href="{{ route('doi.batch-mint') }}" class="btn btn-outline-secondary">Queue Entities for Minting</a>
      </div>
    @endif
  @endif
@endsection
