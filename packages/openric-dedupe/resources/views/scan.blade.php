@extends('theme::layouts.1col')

@section('title', 'Start Duplicate Scan')
@section('body-class', 'admin dedupe scan')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-search me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Start Duplicate Scan</h1>
      <span class="small text-muted">Duplicate Detection</span>
    </div>
    <div class="ms-auto">
      <a href="{{ route('dedupe.dashboard') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>
  </div>

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
          <h5 class="mb-0">Scan Configuration</h5>
        </div>
        <div class="card-body">
          <form method="post" action="{{ route('dedupe.scan.start') }}">
            @csrf
            <div class="mb-4">
              <label class="form-label fw-bold">Entity Type</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="entity_type" id="typeRecordSet" value="RecordSet" checked>
                <label class="form-check-label" for="typeRecordSet">
                  <strong>Record Sets</strong>
                  <br><small class="text-muted">Scan all record set entities for duplicates</small>
                </label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="entity_type" id="typeAgent" value="Agent">
                <label class="form-check-label" for="typeAgent">
                  <strong>Agents</strong>
                  <br><small class="text-muted">Scan agent (person, corporate body, family) entities</small>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="entity_type" id="typeAll" value="all">
                <label class="form-check-label" for="typeAll">
                  <strong>All Entity Types</strong>
                  <br><small class="text-muted">Scan both records and agents</small>
                </label>
              </div>
            </div>

            <div class="mb-4">
              <label for="threshold" class="form-label fw-bold">Similarity Threshold</label>
              <input type="number" class="form-control" id="threshold" name="threshold" min="0.1" max="1.0" step="0.05" value="0.7">
              <div class="form-text">Minimum similarity score (0.0 - 1.0) to flag as a potential duplicate</div>
            </div>

            <div class="mb-4">
              <label for="limit" class="form-label fw-bold">Maximum Results</label>
              <input type="number" class="form-control" id="limit" name="limit" min="10" max="500" value="100">
              <div class="form-text">Maximum number of candidate pairs to return</div>
            </div>

            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Note:</strong> The scan queries the SPARQL triplestore for entities with similar titles, identical identifiers, or matching dates. Results are stored in the duplicate_candidates table for review.
            </div>

            <button type="submit" class="btn atom-btn-outline-success">
              <i class="fas fa-play me-1"></i> Start Scan
            </button>
            <a href="{{ route('dedupe.dashboard') }}" class="btn atom-btn-white">Cancel</a>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Scanning</h5>
        </div>
        <div class="card-body">
          <p>The duplicate scan will:</p>
          <ul>
            <li>Compare entities using SPARQL-based title similarity</li>
            <li>Check for matching identifiers across entities</li>
            <li>Apply configurable threshold scoring</li>
            <li>Store candidate pairs for manual review</li>
          </ul>
          <p class="mb-0"><strong>Tip:</strong> Start with a high threshold (0.85+) for fewer false positives, then lower it to catch more subtle duplicates.</p>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
          <h5 class="mb-0"><i class="fas fa-terminal me-2"></i>CLI Commands</h5>
        </div>
        <div class="card-body">
          <p class="mb-2"><strong>Record scan:</strong></p>
          <pre class="bg-light p-2 rounded mb-3">php artisan dedupe:scan --type=RecordSet</pre>
          <p class="mb-2"><strong>Agent scan:</strong></p>
          <pre class="bg-light p-2 rounded mb-3">php artisan dedupe:scan --type=Agent</pre>
          <p class="mb-2"><strong>Custom threshold:</strong></p>
          <pre class="bg-light p-2 rounded mb-0">php artisan dedupe:scan --threshold=0.85</pre>
        </div>
      </div>
    </div>
  </div>
@endsection
