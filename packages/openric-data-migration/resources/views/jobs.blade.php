@extends('theme::layouts.1col')

@section('title', 'Migration Jobs')
@section('body-class', 'admin data-migration jobs')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-tasks me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Migration Jobs</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="card">
    <div class="card-body p-0">
      @if(empty($jobs))
        <div class="p-4 text-center text-muted"><i class="fas fa-tasks fa-3x mb-3"></i><p>No migration jobs.</p></div>
      @else
        <div class="table-responsive">
          <table class="table table-bordered table-striped mb-0">
            <thead><tr><th>ID</th><th>Type</th><th>Format</th><th>Status</th><th>Progress</th><th>Records</th><th>Errors</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
              @foreach($jobs as $job)
                <tr>
                  <td>{{ $job['id'] ?? '' }}</td>
                  <td><span class="badge bg-light text-dark">{{ $job['entity_type'] ?? '' }}</span></td>
                  <td>{{ $job['source_format'] ?? 'csv' }}</td>
                  <td>
                    @php $sc = match($job['status'] ?? '') { 'completed' => 'bg-success', 'failed' => 'bg-danger', 'processing' => 'bg-primary', 'cancelled' => 'bg-warning text-dark', default => 'bg-secondary' }; @endphp
                    <span class="badge {{ $sc }}">{{ ucfirst($job['status'] ?? 'pending') }}</span>
                  </td>
                  <td>
                    @php $pct = ($job['total_rows'] ?? 0) > 0 ? min(100, round(($job['processed_rows'] ?? 0) / $job['total_rows'] * 100)) : 0; @endphp
                    <div class="progress" style="height:18px"><div class="progress-bar" style="width:{{ $pct }}%">{{ $pct }}%</div></div>
                  </td>
                  <td>{{ number_format($job['processed_rows'] ?? 0) }}/{{ number_format($job['total_rows'] ?? 0) }}</td>
                  <td>{{ $job['error_rows'] ?? 0 }}</td>
                  <td><small>{{ isset($job['created_at']) ? \Carbon\Carbon::parse($job['created_at'])->format('M j, H:i') : '' }}</small></td>
                  <td><a href="{{ route('data-migration.job', $job['id']) }}" class="btn btn-sm atom-btn-white"><i class="fas fa-eye"></i></a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endsection
