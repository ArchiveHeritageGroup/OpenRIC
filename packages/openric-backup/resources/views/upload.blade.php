@extends('theme::layouts.1col')
@section('title', 'Upload Backup')
@section('body-class', 'admin backup upload')

@section('content')
<div class="multiline-header d-flex align-items-center mb-3">
  <i class="fas fa-3x fa-cloud-upload-alt me-3" aria-hidden="true"></i>
  <div class="d-flex flex-column">
    <h1 class="mb-0">Upload Backup</h1>
    <span class="small text-muted">Upload an external backup file for import or restore</span>
  </div>
</div>

<div class="mb-3">
  <a href="{{ route('backups.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> Back to Backups
  </a>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card mb-4">
  <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
    <i class="fas fa-upload me-1"></i> Upload Backup File
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('backups.doUpload') }}" enctype="multipart/form-data">
      @csrf

      <div class="mb-3">
        <label for="backup_file" class="form-label">Backup Archive <span class="text-danger">*</span></label>
        <input type="file" class="form-control @error('backup_file') is-invalid @enderror"
               id="backup_file" name="backup_file"
               accept=".gz,.tar.gz,.sql.gz,.nq.gz,.zip" required>
        <div class="form-text">
          Accepted formats: <code>.sql.gz</code> (PostgreSQL dump), <code>.nq.gz</code> (N-Quads triplestore),
          <code>.tar.gz</code> (uploads, packages, framework), <code>.zip</code>.
          Maximum file size: 5 GB.
        </div>
        @error('backup_file')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="alert alert-info">
        <i class="fas fa-info-circle me-1"></i>
        The backup type will be auto-detected from the filename. Use naming conventions like
        <code>database_*.sql.gz</code>, <code>triplestore_*.nq.gz</code>, <code>uploads_*.tar.gz</code>,
        <code>packages_*.tar.gz</code>, or <code>framework_*.tar.gz</code>.
      </div>

      <div class="mt-3 p-3 rounded" style="background:#495057;">
        <button type="submit" class="btn btn-success">
          <i class="fas fa-cloud-upload-alt me-1"></i> Upload
        </button>
        <a href="{{ route('backups.index') }}" class="btn btn-outline-light ms-2">
          <i class="fas fa-times me-1"></i> Cancel
        </a>
      </div>
    </form>
  </div>
</div>
@endsection
