@extends('theme::layouts.1col')

@section('title', 'Import Results')
@section('body-class', 'admin data-migration import-results')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-check-circle me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Import Results</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  @if($result)
    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="card text-center {{ $result['success'] ? 'border-success' : 'border-danger' }}"><div class="card-body"><h3 class="{{ $result['success'] ? 'text-success' : 'text-danger' }}">{{ $result['success'] ? 'Success' : 'Failed' }}</h3><p class="text-muted mb-0">Status</p></div></div></div>
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="text-success">{{ number_format($result['imported'] ?? 0) }}</h3><p class="text-muted mb-0">Imported</p></div></div></div>
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="text-info">{{ number_format($result['updated'] ?? 0) }}</h3><p class="text-muted mb-0">Updated</p></div></div></div>
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="text-danger">{{ number_format($result['errors'] ?? 0) }}</h3><p class="text-muted mb-0">Errors</p></div></div></div>
    </div>
    @if(!empty($result['message']))<div class="alert alert-info">{{ $result['message'] }}</div>@endif
  @endif

  @if(!empty($results))
    <div class="card">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Imported Records</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive"><table class="table table-bordered table-striped mb-0">
          <thead><tr>@foreach(array_keys($results[0] ?? []) as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
          <tbody>@foreach($results as $row)<tr>@foreach($row as $v)<td><small>{{ $v }}</small></td>@endforeach</tr>@endforeach</tbody>
        </table></div>
      </div>
    </div>
  @endif
@endsection
