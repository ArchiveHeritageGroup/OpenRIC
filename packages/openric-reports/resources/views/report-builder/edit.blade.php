@extends('theme::layouts.1col')
@section('title', 'Edit Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-pencil-alt me-2"></i>Edit Report</h1>
      <a href="{{ route('reports.builder.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(isset($report))
      <div class="card mb-3">
        <div class="card-header bg-primary text-white"><i class="fas fa-pencil-alt me-2"></i>Report Details</div>
        <div class="card-body">
          <form method="post" action="{{ route('reports.builder.update', $report->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required value="{{ $report->name ?? '' }}">
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3">{{ $report->description ?? '' }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
              <input type="text" name="category" class="form-control" value="{{ $report->category ?? 'General' }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header bg-primary text-white"><i class="fas fa-info-circle me-2"></i>Report Metadata</div>
        <div class="card-body">
          <table class="table table-sm">
            <tr><th width="150">ID</th><td>{{ $report->id ?? '-' }}</td></tr>
            <tr><th>Data Source</th><td>{{ ucfirst($report->data_source ?? '-') }}</td></tr>
            <tr><th>Status</th><td><span class="badge bg-secondary">{{ ucfirst($report->status ?? 'draft') }}</span></td></tr>
            <tr><th>Created</th><td>{{ $report->created_at ?? '-' }}</td></tr>
            <tr><th>Updated</th><td>{{ $report->updated_at ?? '-' }}</td></tr>
          </table>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('reports.builder.preview', $report->id) }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye me-1"></i>Preview</a>
        <a href="{{ route('reports.builder.query', $report->id) }}" class="btn btn-outline-info btn-sm"><i class="fas fa-database me-1"></i>Query Builder</a>
        <a href="{{ route('reports.builder.schedule', $report->id) }}" class="btn btn-outline-warning btn-sm"><i class="fas fa-calendar me-1"></i>Schedule</a>
        <a href="{{ route('reports.builder.share', $report->id) }}" class="btn btn-outline-success btn-sm"><i class="fas fa-share me-1"></i>Share</a>
        <a href="{{ route('reports.builder.history', $report->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-history me-1"></i>History</a>
        <a href="{{ route('reports.builder.widget', $report->id) }}" class="btn btn-outline-dark btn-sm"><i class="fas fa-th me-1"></i>Widgets</a>
        <a href="{{ route('reports.builder.clone', $report->id) }}" class="btn btn-outline-info btn-sm"><i class="fas fa-copy me-1"></i>Clone</a>
        <a href="{{ route('reports.builder.export', ['id' => $report->id, 'format' => 'csv']) }}" class="btn btn-outline-success btn-sm"><i class="fas fa-download me-1"></i>Export CSV</a>
        <a href="{{ route('reports.builder.export', ['id' => $report->id, 'format' => 'json']) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-code me-1"></i>Export JSON</a>
      </div>
    @else
      <div class="card"><div class="card-body text-muted text-center py-4">No report data available.</div></div>
    @endif
  </div>
</div>
@endsection
