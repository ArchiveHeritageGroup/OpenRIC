@extends('theme::layouts.1col')
@section('title', 'Archived Reports')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-archive me-2"></i>Archived Reports</h1>
      <a href="{{ route('reports.builder.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white">Archived Reports</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
          <thead><tr><th>Name</th><th>Source</th><th>Category</th><th>Updated</th><th>Actions</th></tr></thead>
          <tbody>
            @forelse($reports ?? collect() as $report)
            <tr>
              <td>{{ $report->name ?? '' }}</td>
              <td>{{ ucfirst($report->data_source ?? '') }}</td>
              <td>{{ $report->category ?? '' }}</td>
              <td>{{ $report->updated_at ?? '' }}</td>
              <td>
                <a href="{{ route('reports.builder.preview', $report->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i>View</a>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted text-center py-3">No archived reports.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
