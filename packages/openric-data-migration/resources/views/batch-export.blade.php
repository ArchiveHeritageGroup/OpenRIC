@extends('theme::layouts.1col')

@section('title', 'Batch Export')
@section('body-class', 'admin data-migration batch-export')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-database me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Batch Export</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  <div class="row g-3 mb-4">
    @foreach($counts ?? [] as $type => $count)
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <h3>{{ number_format($count) }}</h3>
            <p class="text-muted mb-2">{{ ucfirst(str_replace('_', ' ', $type)) }}</p>
            <a href="{{ route('data-migration.batch-export', ['export' => 1, 'entity_type' => $type]) }}" class="btn btn-sm atom-btn-outline-success"><i class="fas fa-download me-1"></i> Export CSV</a>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card">
    <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Custom Export</strong></div>
    <div class="card-body">
      <form method="get" action="{{ route('data-migration.batch-export') }}" class="row g-3 align-items-end">
        <input type="hidden" name="export" value="1">
        <div class="col-md-3">
          <label class="form-label">Entity Type</label>
          <select name="entity_type" class="form-select" required>
            <option value="record">Records</option><option value="agent">Agents</option><option value="accession">Accessions</option><option value="repository">Repositories</option>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label">Date from</label><input type="date" name="date_from" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Date to</label><input type="date" name="date_to" class="form-control"></div>
        <div class="col-md-3"><button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-download me-1"></i> Export</button></div>
      </form>
    </div>
  </div>
@endsection
