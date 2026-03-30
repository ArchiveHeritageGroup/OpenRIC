@extends('theme::layouts.1col')

@section('title', 'Job Status')
@section('body-class', 'admin data-migration job-status')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-cog me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Job #{{ $job['id'] ?? '' }}</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('data-migration.jobs') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> All Jobs</a>
      @if(in_array($job['status'] ?? '', ['pending', 'processing']))
        <form method="post" action="{{ route('data-migration.cancel-job') }}" class="d-inline">@csrf<input type="hidden" name="id" value="{{ $job['id'] }}"><button type="submit" class="btn atom-btn-outline-danger"><i class="fas fa-stop me-1"></i> Cancel</button></form>
      @endif
    </div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="row mb-4">
    <div class="col-md-3"><div class="card text-center"><div class="card-body">
      @php $sc = match($job['status'] ?? '') { 'completed' => 'text-success', 'failed' => 'text-danger', 'processing' => 'text-primary', default => 'text-secondary' }; @endphp
      <h4 class="{{ $sc }}">{{ ucfirst($job['status'] ?? 'pending') }}</h4><p class="text-muted mb-0">Status</p>
    </div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4>{{ $progressPercent ?? 0 }}%</h4><p class="text-muted mb-0">Progress</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4>{{ number_format($job['processed_rows'] ?? 0) }}/{{ number_format($job['total_rows'] ?? 0) }}</h4><p class="text-muted mb-0">Records</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4 class="text-danger">{{ $job['error_rows'] ?? 0 }}</h4><p class="text-muted mb-0">Errors</p></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Progress</strong></div>
    <div class="card-body">
      <div class="progress mb-3" style="height:24px"><div class="progress-bar {{ ($job['status'] ?? '') === 'failed' ? 'bg-danger' : '' }}" id="jobProgress" style="width:{{ $progressPercent ?? 0 }}%">{{ $progressPercent ?? 0 }}%</div></div>
      <p class="text-muted mb-0" id="jobMessage">{{ $job['progress_message'] ?? '' }}</p>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Details</strong></div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3">Entity Type</dt><dd class="col-sm-9">{{ $job['entity_type'] ?? '' }}</dd>
        <dt class="col-sm-3">Source Format</dt><dd class="col-sm-9">{{ $job['source_format'] ?? 'csv' }}</dd>
        <dt class="col-sm-3">Source File</dt><dd class="col-sm-9">{{ $job['source_file'] ?? '' }}</dd>
        <dt class="col-sm-3">Created</dt><dd class="col-sm-9">{{ isset($job['created_at']) ? \Carbon\Carbon::parse($job['created_at'])->format('Y-m-d H:i:s') : '' }}</dd>
        <dt class="col-sm-3">Completed</dt><dd class="col-sm-9">{{ isset($job['completed_at']) ? \Carbon\Carbon::parse($job['completed_at'])->format('Y-m-d H:i:s') : '-' }}</dd>
      </dl>
    </div>
  </div>

  @if(($job['status'] ?? '') === 'completed')
    <div class="d-flex gap-2">
      <a href="{{ route('data-migration.export-csv', ['job_id' => $job['id']]) }}" class="btn atom-btn-white"><i class="fas fa-download me-1"></i> Export Results CSV</a>
      <form method="post" action="{{ route('data-migration.rollback') }}">@csrf<input type="hidden" name="job_id" value="{{ $job['id'] }}"><button type="submit" class="btn atom-btn-outline-danger" onclick="return confirm('Rollback this import? This will delete all records created by this job.')"><i class="fas fa-undo me-1"></i> Rollback</button></form>
    </div>
  @endif
@endsection

@push('js')
@if(in_array($job['status'] ?? '', ['pending', 'processing']))
<script>
(function poll() {
    fetch('{{ route("data-migration.job-progress") }}?id={{ $job["id"] }}')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('jobProgress').style.width = data.progress + '%';
            document.getElementById('jobProgress').textContent = data.progress + '%';
            document.getElementById('jobMessage').textContent = data.message || '';
            if (data.status === 'processing' || data.status === 'pending') {
                setTimeout(poll, 2000);
            } else {
                location.reload();
            }
        })
        .catch(function() { setTimeout(poll, 5000); });
})();
</script>
@endif
@endpush
