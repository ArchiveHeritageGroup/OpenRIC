@extends('theme::layouts.1col')

@section('title', 'Upload Import File')
@section('body-class', 'admin data-migration upload')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-upload me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Upload Import File</h1><span class="small text-muted">Data Migration</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.index') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a></div>
  </div>

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><h5 class="mb-0">Upload CSV File</h5></div>
        <div class="card-body">
          <form method="post" action="{{ route('data-migration.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label for="file" class="form-label">CSV File <span class="badge bg-danger ms-1">Required</span></label>
              <input type="file" class="form-control" id="file" name="file" accept=".csv,.tsv,.txt" required>
              <div class="form-text">Maximum file size: 100MB. Supported formats: CSV, TSV.</div>
            </div>
            <div class="mb-3">
              <label for="target_type" class="form-label">Target Entity Type <span class="badge bg-danger ms-1">Required</span></label>
              <select class="form-select" id="target_type" name="target_type" required>
                <option value="">-- Select target type --</option>
                <option value="record">Record Descriptions</option>
                <option value="agent">Agents</option>
                <option value="accession">Accessions</option>
                <option value="repository">Repositories</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="import_type" class="form-label">Import Mode</label>
              <select class="form-select" id="import_type" name="import_type">
                <option value="create">Create new records</option>
                <option value="update">Update existing records</option>
                <option value="create_or_update">Create or update</option>
              </select>
            </div>
            <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-upload me-1"></i> Upload and Continue</button>
            <a href="{{ route('data-migration.index') }}" class="btn atom-btn-white">Cancel</a>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Import Help</h5></div>
        <div class="card-body">
          <p>The import wizard will guide you through:</p>
          <ol><li>Upload your CSV file</li><li>Map source columns to target fields</li><li>Preview the mapped data</li><li>Execute the import</li></ol>
          <hr>
          <p class="mb-0"><strong>Tip:</strong> Use UTF-8 encoding and include a header row in your CSV file.</p>
        </div>
      </div>

      @if(!empty($savedMappings))
      <div class="card mt-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><h5 class="mb-0">Saved Mappings</h5></div>
        <ul class="list-group list-group-flush">
          @foreach($savedMappings as $mapping)
            <li class="list-group-item"><strong>{{ $mapping['name'] ?? '' }}</strong><br><small class="text-muted">{{ $mapping['entity_type'] ?? '' }}</small></li>
          @endforeach
        </ul>
      </div>
      @endif
    </div>
  </div>
@endsection
