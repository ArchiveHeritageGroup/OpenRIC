@extends('theme::layouts.1col')
@section('title', 'Preview Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-eye me-2"></i>Preview Report</h1>
      <div>
        @if(isset($report))
        <a href="{{ route('reports.builder.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-pencil-alt me-1"></i>Edit</a>
        <a href="{{ route('reports.builder.export', ['id' => $report->id, 'format' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-download me-1"></i>CSV</a>
        @endif
        <a href="{{ route('reports.builder.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
      </div>
    </div>

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">{{ $report->name ?? 'Report' }}</div>
      <div class="card-body">
        @if($report->description ?? null)
          <p class="text-muted">{{ $report->description }}</p>
        @endif
        <table class="table table-sm">
          <tr><th width="150">Data Source</th><td>{{ ucfirst($report->data_source ?? '-') }}</td></tr>
          <tr><th>Category</th><td>{{ $report->category ?? '-' }}</td></tr>
          <tr><th>Status</th><td><span class="badge bg-secondary">{{ ucfirst($report->status ?? 'draft') }}</span></td></tr>
          <tr><th>Created</th><td>{{ $report->created_at ?? '-' }}</td></tr>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white"><i class="fas fa-table me-2"></i>Data Preview</div>
      <div class="card-body" id="reportData">
        <p class="text-muted text-center py-3">Click "Load Data" to preview results.</p>
        <button class="btn btn-primary btn-sm" id="loadDataBtn"><i class="fas fa-play me-1"></i>Load Data</button>
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
document.getElementById('loadDataBtn')?.addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';

    fetch('{{ route("reports.api.data", $report->id) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data && data.data.length > 0) {
            var cols = Object.keys(data.data[0]);
            var html = '<p class="mb-2"><strong>' + data.count + '</strong> rows returned.</p>';
            html += '<div class="table-responsive"><table class="table table-bordered table-sm table-striped"><thead><tr>';
            cols.forEach(c => html += '<th>' + c + '</th>');
            html += '</tr></thead><tbody>';
            data.data.slice(0, 100).forEach(row => {
                html += '<tr>';
                cols.forEach(c => html += '<td>' + (row[c] ?? '') + '</td>');
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            if (data.count > 100) html += '<p class="text-muted">Showing first 100 of ' + data.count + ' rows.</p>';
            document.getElementById('reportData').innerHTML = html;
        } else {
            document.getElementById('reportData').innerHTML = '<div class="alert alert-info">' + (data.error || 'No data returned.') + '</div>';
        }
    })
    .catch(e => {
        document.getElementById('reportData').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
    });
});
</script>
@endpush
@endif
@endsection
