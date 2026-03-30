@extends('theme::layouts.1col')

@section('title', 'Data Migration')
@section('body-class', 'admin data-migration')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-exchange-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Data Migration</h1>
      <span class="small text-muted">Import, export, and manage data migrations</span>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('data-migration.upload') }}" class="btn atom-btn-outline-success"><i class="fas fa-upload me-1"></i> Import</a>
      <a href="{{ route('data-migration.export') }}" class="btn atom-btn-white"><i class="fas fa-download me-1"></i> Export</a>
      <a href="{{ route('data-migration.jobs') }}" class="btn atom-btn-white"><i class="fas fa-tasks me-1"></i> Jobs</a>
    </div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  {{-- Stats --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-3 fw-bold text-primary">{{ number_format($stats['total_jobs'] ?? 0) }}</div><div class="small text-muted">Total Jobs</div></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-3 fw-bold text-success">{{ number_format($stats['completed_jobs'] ?? 0) }}</div><div class="small text-muted">Completed</div></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-3 fw-bold text-danger">{{ number_format($stats['failed_jobs'] ?? 0) }}</div><div class="small text-muted">Failed</div></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-3 fw-bold text-info">{{ number_format($stats['total_records'] ?? 0) }}</div><div class="small text-muted">Records Migrated</div></div></div></div>
  </div>

  <div class="row">
    {{-- Recent Jobs --}}
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Recent Jobs</strong></div>
        <div class="card-body p-0">
          @if(empty($recentJobs))
            <div class="p-3 text-muted">No recent migration jobs.</div>
          @else
            <div class="table-responsive">
              <table class="table table-bordered table-striped mb-0">
                <thead><tr><th>ID</th><th>Type</th><th>Status</th><th>Progress</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                  @foreach($recentJobs as $job)
                    <tr>
                      <td>{{ $job['id'] ?? '' }}</td>
                      <td><span class="badge bg-light text-dark">{{ $job['entity_type'] ?? $job['target_type'] ?? '' }}</span></td>
                      <td>
                        @php $statusClass = match($job['status'] ?? '') { 'completed' => 'bg-success', 'failed' => 'bg-danger', 'processing' => 'bg-primary', default => 'bg-secondary' }; @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($job['status'] ?? 'pending') }}</span>
                      </td>
                      <td>
                        @php $pct = ($job['total_rows'] ?? 0) > 0 ? min(100, round(($job['processed_rows'] ?? 0) / $job['total_rows'] * 100)) : 0; @endphp
                        <div class="progress" style="height:18px"><div class="progress-bar" style="width:{{ $pct }}%">{{ $pct }}%</div></div>
                      </td>
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
    </div>

    {{-- Saved Mappings --}}
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Saved Mappings</strong></div>
        <ul class="list-group list-group-flush">
          @forelse($mappings ?? [] as $mapping)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong>{{ $mapping['name'] ?? '' }}</strong>
                <br><small class="text-muted">{{ $mapping['entity_type'] ?? $mapping['target_type'] ?? '' }}</small>
              </div>
              <form method="post" action="{{ route('data-migration.delete-mapping', $mapping['id']) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm atom-btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
              </form>
            </li>
          @empty
            <li class="list-group-item text-muted">No saved mappings.</li>
          @endforelse
        </ul>
      </div>

      {{-- Quick Links --}}
      <div class="card mb-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Quick Links</strong></div>
        <div class="list-group list-group-flush">
          <a href="{{ route('data-migration.upload') }}" class="list-group-item list-group-item-action"><i class="fas fa-upload me-2"></i> CSV Import</a>
          <a href="{{ route('data-migration.batch-export') }}" class="list-group-item list-group-item-action"><i class="fas fa-download me-2"></i> Batch Export</a>
          <a href="{{ route('data-migration.preservica-import') }}" class="list-group-item list-group-item-action"><i class="fas fa-cloud-download-alt me-2"></i> Preservica Import</a>
          <a href="{{ route('data-migration.preservica-export') }}" class="list-group-item list-group-item-action"><i class="fas fa-cloud-upload-alt me-2"></i> Preservica Export</a>
          <a href="{{ route('data-migration.history') }}" class="list-group-item list-group-item-action"><i class="fas fa-history me-2"></i> History</a>
        </div>
      </div>
    </div>
  </div>
@endsection
