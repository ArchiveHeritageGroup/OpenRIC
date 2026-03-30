@extends('theme::layouts.1col')

@section('title', 'Export Data')
@section('body-class', 'admin data-migration export')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-download me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Export Data</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <form method="post" action="{{ route('data-migration.export') }}">
    @csrf
    <div class="card">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><h5 class="mb-0">Export Configuration</h5></div>
      <div class="card-body">
        <div class="mb-3">
          <label for="target_type" class="form-label">Entity Type <span class="badge bg-danger ms-1">Required</span></label>
          <select class="form-select" id="target_type" name="target_type" required>
            <option value="record">Record Descriptions</option>
            <option value="agent">Agents</option>
            <option value="accession">Accessions</option>
            <option value="repository">Repositories</option>
          </select>
        </div>
        <div class="row">
          <div class="col-md-4"><div class="mb-3"><label for="export_type" class="form-label">Format</label><select class="form-select" id="export_type" name="export_type"><option value="csv">CSV</option><option value="json">JSON</option></select></div></div>
          <div class="col-md-4"><div class="mb-3"><label for="date_from" class="form-label">Date from</label><input type="date" class="form-control" id="date_from" name="date_from"></div></div>
          <div class="col-md-4"><div class="mb-3"><label for="date_to" class="form-label">Date to</label><input type="date" class="form-control" id="date_to" name="date_to"></div></div>
        </div>
        <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-download me-1"></i> Start Export</button>
      </div>
    </div>
  </form>
@endsection
