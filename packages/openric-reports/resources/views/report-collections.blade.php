@extends('theme::layouts.1col')
@section('title', 'Collection Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-layer-group me-2"></i>Collection Report</h1>
      <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</a>
    </div>
    <p class="text-muted">Collection-level statistics from the RiC-O triplestore (RecordSets with record counts).</p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm">
        <thead><tr><th>IRI</th><th>Title</th><th class="text-end">Records</th></tr></thead>
        <tbody>
          @forelse($data ?? [] as $row)
            <tr>
              <td><code class="small">{{ Str::limit($row['iri'] ?? '', 60) }}</code></td>
              <td>{{ $row['title'] ?? '' }}</td>
              <td class="text-end"><span class="badge bg-primary">{{ number_format($row['records'] ?? 0) }}</span></td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-muted text-center">No collections found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
