@extends('theme::layouts.1col')

@section('title', 'Preview Import')
@section('body-class', 'admin data-migration preview')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-eye me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Preview Import</h1><span class="small text-muted">{{ number_format($totalRows ?? 0) }} total rows &middot; Target: {{ ucfirst($targetType ?? '') }}</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.map') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Back to Mapping</a></div>
  </div>

  @if(!empty($transformedRows))
    <div class="card mb-4">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Preview (first {{ count($transformedRows) }} rows)</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped mb-0">
            <thead><tr>@foreach($targetHeaders ?? [] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>
              @foreach($transformedRows as $row)
                <tr>@foreach($targetHeaders ?? [] as $header)<td><small>{{ $row[$header] ?? '' }}</small></td>@endforeach</tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <form method="post" action="{{ route('data-migration.execute') }}">
      @csrf
      <input type="hidden" name="column_mapping" value="{{ json_encode($mapping ?? []) }}">
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Ready to import {{ number_format($totalRows ?? 0) }} records.</strong> Review the preview data above before proceeding.
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-play me-1"></i> Execute Import</button>
        <a href="{{ route('data-migration.map') }}" class="btn atom-btn-white">Cancel</a>
      </div>
    </form>
  @else
    <div class="alert alert-info">No preview data available. Please go back and configure the column mapping.</div>
  @endif
@endsection
