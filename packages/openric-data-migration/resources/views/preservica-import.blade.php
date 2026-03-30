@extends('theme::layouts.1col')

@section('title', 'Preservica Import')
@section('body-class', 'admin data-migration preservica-import')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-cloud-download-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Preservica Import</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  <form method="post" action="{{ route('data-migration.preservica-import') }}">
    @csrf
    <div class="card">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><h5 class="mb-0">Preservica Connection</h5></div>
      <div class="card-body">
        <div class="mb-3"><label class="form-label">Preservica API URL <span class="badge bg-danger ms-1">Required</span></label><input type="url" class="form-control" name="preservica_url" placeholder="https://preservica.example.com/api" required></div>
        <div class="mb-3"><label class="form-label">Username <span class="badge bg-danger ms-1">Required</span></label><input type="text" class="form-control" name="preservica_user" required></div>
        <div class="mb-3"><label class="form-label">Collection Reference</label><input type="text" class="form-control" name="collection_ref" placeholder="e.g., SO_12345"></div>
        <div class="mb-3"><label class="form-label">Target Repository</label><input type="text" class="form-control" name="target_repository"></div>
        <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-cloud-download-alt me-1"></i> Start Import</button>
        <a href="{{ route('data-migration.index') }}" class="btn atom-btn-white">Cancel</a>
      </div>
    </div>
  </form>
@endsection
