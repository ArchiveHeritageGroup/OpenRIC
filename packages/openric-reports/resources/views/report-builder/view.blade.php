@extends('theme::layouts.1col')
@section('title', 'View Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-file-alt me-2"></i>{{ $report->name ?? 'Report' }}</h1>
      <a href="{{ route('reports.builder.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">Report Details</div>
      <div class="card-body">
        @if($report->description)<p>{{ $report->description }}</p>@endif
        <table class="table table-sm mb-0">
          <tr><th width="150">ID</th><td>{{ $report->id }}</td></tr>
          <tr><th>Data Source</th><td>{{ ucfirst($report->data_source ?? '-') }}</td></tr>
          <tr><th>Category</th><td>{{ $report->category ?? '-' }}</td></tr>
          <tr><th>Status</th><td><span class="badge bg-secondary">{{ ucfirst($report->status ?? 'draft') }}</span></td></tr>
          <tr><th>Visibility</th><td>
            @if($report->is_public ?? false) <span class="badge bg-success">Public</span>
            @elseif($report->is_shared ?? false) <span class="badge bg-info">Shared</span>
            @else <span class="badge bg-secondary">Private</span> @endif
          </td></tr>
          <tr><th>Created</th><td>{{ $report->created_at ?? '-' }}</td></tr>
          <tr><th>Updated</th><td>{{ $report->updated_at ?? '-' }}</td></tr>
        </table>
      </div>
    </div>
    @else
    <div class="alert alert-warning">Report not found.</div>
    @endif
  </div>
</div>
@endsection
