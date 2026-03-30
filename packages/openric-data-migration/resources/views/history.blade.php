@extends('theme::layouts.1col')

@section('title', 'Import History')
@section('body-class', 'admin data-migration history')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-history me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Import History</h1><span class="small text-muted">{{ number_format($total ?? 0) }} total jobs</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  @if(!empty($jobs))
    <div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered table-striped mb-0">
      <thead><tr><th>ID</th><th>Type</th><th>Status</th><th>Records</th><th>Errors</th><th>Created</th><th>Completed</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($jobs as $job)
          <tr>
            <td>{{ $job['id'] ?? '' }}</td>
            <td>{{ $job['entity_type'] ?? '' }}</td>
            <td><span class="badge {{ ($job['status'] ?? '') === 'completed' ? 'bg-success' : (($job['status'] ?? '') === 'failed' ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst($job['status'] ?? '') }}</span></td>
            <td>{{ number_format($job['processed_rows'] ?? 0) }}/{{ number_format($job['total_rows'] ?? 0) }}</td>
            <td>{{ $job['error_rows'] ?? 0 }}</td>
            <td><small>{{ isset($job['created_at']) ? \Carbon\Carbon::parse($job['created_at'])->format('Y-m-d H:i') : '' }}</small></td>
            <td><small>{{ isset($job['completed_at']) ? \Carbon\Carbon::parse($job['completed_at'])->format('Y-m-d H:i') : '-' }}</small></td>
            <td><a href="{{ route('data-migration.job', $job['id']) }}" class="btn btn-sm atom-btn-white"><i class="fas fa-eye"></i></a></td>
          </tr>
        @endforeach
      </tbody>
    </table></div></div></div>

    @if(($pages ?? 1) > 1)
      <nav class="mt-3"><ul class="pagination">
        @if($page > 1)<li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">Previous</a></li>@endif
        <li class="page-item disabled"><span class="page-link">Page {{ $page }} of {{ $pages }}</span></li>
        @if($page < $pages)<li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">Next</a></li>@endif
      </ul></nav>
    @endif
  @else
    <div class="alert alert-info">No import history available.</div>
  @endif
@endsection
