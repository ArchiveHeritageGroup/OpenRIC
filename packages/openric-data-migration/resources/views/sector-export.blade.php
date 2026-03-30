@extends('theme::layouts.1col')

@section('title', 'Sector Export')
@section('body-class', 'admin data-migration sector-export')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-industry me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Sector Export</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  <div class="card">
    <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><h5 class="mb-0">Sector Export Configuration</h5></div>
    <div class="card-body">
      <form method="post" action="{{ route('data-migration.sector-export') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Sector</label><input type="text" class="form-control" name="sector" placeholder="e.g., heritage, museum, library"></div>
        <div class="mb-3"><label class="form-label">Export Format</label><select class="form-select" name="format"><option value="csv">CSV</option><option value="xml">XML (EAD)</option><option value="json">JSON-LD</option></select></div>
        <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-download me-1"></i> Export</button>
      </form>
    </div>
  </div>
@endsection
