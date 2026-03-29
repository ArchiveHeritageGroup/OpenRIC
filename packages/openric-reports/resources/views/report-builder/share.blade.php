@extends('theme::layouts.1col')
@section('title', 'Share Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-share me-2"></i>Share Report</h1>
      @if(isset($report))
      <a href="{{ route('reports.builder.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
      @endif
    </div>

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">{{ $report->name ?? 'Report' }} -- Sharing</div>
      <div class="card-body">
        <p>Create a shareable link for this report. Links can be time-limited and deactivated.</p>
        <button id="createShareBtn" class="btn btn-primary btn-sm mb-3"><i class="fas fa-link me-1"></i>Create Share Link</button>
        <div id="shareResult"></div>
      </div>
    </div>

    @if(($shares ?? collect())->count() > 0)
    <div class="card">
      <div class="card-header bg-primary text-white"><i class="fas fa-list me-2"></i>Existing Share Links</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
          <thead><tr><th>Token</th><th>Status</th><th>Created</th><th>Expires</th></tr></thead>
          <tbody>
            @foreach($shares as $s)
            <tr>
              <td><code>{{ Str::limit($s->token ?? '', 20) }}</code></td>
              <td>{!! ($s->is_active ?? false) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td>
              <td>{{ $s->created_at ?? '' }}</td>
              <td>{{ $s->expires_at ?? 'Never' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
    @else
    <div class="alert alert-warning">Report not found.</div>
    @endif
  </div>
</div>

@if(isset($report))
@push('js')
<script>
document.getElementById('createShareBtn')?.addEventListener('click', function() {
    fetch('{{ route("reports.api.share.create", $report->id) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        var el = document.getElementById('shareResult');
        if (data.success) {
            el.innerHTML = '<div class="alert alert-success">Share link created: <a href="' + data.url + '">' + data.url + '</a></div>';
        } else {
            el.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
        }
    });
});
</script>
@endpush
@endif
@endsection
