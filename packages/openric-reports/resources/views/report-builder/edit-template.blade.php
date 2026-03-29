@extends('theme::layouts.1col')
@section('title', 'Edit Template')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-pencil-alt me-2"></i>Edit Template</h1>
      <a href="{{ route('reports.builder.templates') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white">Template Configuration</div>
      <div class="card-body">
        @if(isset($report))
          <table class="table table-sm">
            <tr><th width="150">ID</th><td>{{ $report->id ?? '-' }}</td></tr>
            <tr><th>Name</th><td>{{ $report->name ?? '-' }}</td></tr>
            <tr><th>Description</th><td>{{ $report->description ?? '-' }}</td></tr>
            <tr><th>Category</th><td>{{ $report->category ?? '-' }}</td></tr>
            <tr><th>Updated</th><td>{{ $report->updated_at ?? '-' }}</td></tr>
          </table>
        @else
          <p class="text-muted text-center py-4">Template not found.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
