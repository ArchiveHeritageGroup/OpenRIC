@extends('theme::layouts.1col')
@section('title', 'Report History')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-history me-2"></i>Report History</h1>
      @if(isset($report))
      <a href="{{ route('reports.builder.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
      @endif
    </div>

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">{{ $report->name ?? 'Report' }} -- Version History</div>
      <div class="card-body">
        <button id="createVersionBtn" class="btn btn-primary btn-sm mb-3"><i class="fas fa-plus me-1"></i>Create Version Snapshot</button>
        <div id="versionResult"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white"><i class="fas fa-list me-2"></i>Versions</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
          <thead><tr><th>Label</th><th>Notes</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>
            @forelse($versions ?? collect() as $v)
            <tr>
              <td>{{ $v->version_label ?? '' }}</td>
              <td>{{ Str::limit($v->notes ?? '', 60) }}</td>
              <td>{{ $v->created_at ?? '' }}</td>
              <td><button class="btn btn-sm btn-outline-warning restore-btn" data-version="{{ $v->id }}"><i class="fas fa-undo me-1"></i>Restore</button></td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-muted text-center">No versions yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @else
    <div class="alert alert-warning">Report not found.</div>
    @endif
  </div>
</div>

@if(isset($report))
@push('js')
<script>
document.getElementById('createVersionBtn')?.addEventListener('click', function() {
    fetch('{{ route("reports.api.version.create", $report->id) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('versionResult').innerHTML = data.success
            ? '<div class="alert alert-success">Version created.</div>'
            : '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
        if (data.success) setTimeout(() => location.reload(), 1000);
    });
});
</script>
@endpush
@endif
@endsection
