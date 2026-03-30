@extends('theme::layouts.1col')

@section('title', 'Batch Mint DOIs')
@section('body-class', 'admin doi batch-mint')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-layer-group me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column flex-grow-1">
      <h1 class="mb-0">Batch Mint DOIs</h1>
      <span class="small text-muted">Queue multiple entities for DOI minting</span>
    </div>
    <div class="ms-auto">
      <a href="{{ route('doi.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Dashboard
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
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

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header fw-bold bg-primary text-white">
          <i class="fas fa-layer-group me-2"></i>Batch Mint
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('doi.batch-mint') }}">
            @csrf

            <div class="mb-3">
              <label for="entity_iris" class="form-label">
                Entity IRIs <span class="text-danger">*</span>
              </label>
              <textarea class="form-control font-monospace" id="entity_iris" name="entity_iris"
                        rows="10" required
                        placeholder="Enter one entity IRI per line, e.g.:&#10;https://example.org/rico/record/001&#10;https://example.org/rico/record/002&#10;https://example.org/rico/record/003">{{ old('entity_iris') }}</textarea>
              <div class="form-text">
                One entity IRI per line. Entities that already have DOIs or are already in the queue will be skipped.
              </div>
            </div>

            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              Batch minting adds entities to the processing queue. Use
              <code>php artisan doi:process-queue</code> or wait for the scheduled
              task to process queued items.
            </div>

            <div class="d-flex gap-2 mt-3">
              <button type="submit" class="btn btn-success">
                <i class="fas fa-paper-plane me-1"></i> Queue for Minting
              </button>
              <a href="{{ route('doi.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i> Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header fw-bold bg-primary text-white">
          <i class="fas fa-info-circle me-2"></i>How It Works
        </div>
        <div class="card-body">
          <ol class="small">
            <li class="mb-2">Enter one entity IRI per line in the text area</li>
            <li class="mb-2">Entities that already have DOIs are automatically skipped</li>
            <li class="mb-2">Valid entities are added to the minting queue with "pending" status</li>
            <li class="mb-2">The queue processor mints DOIs via the DataCite API</li>
            <li class="mb-2">Failed items can be retried from the <a href="{{ route('doi.queue') }}">Queue</a> page</li>
          </ol>
        </div>
      </div>

      <div class="card">
        <div class="card-header fw-bold bg-primary text-white">
          <i class="fas fa-chart-pie me-2"></i>Current Queue Status
        </div>
        <div class="card-body text-center">
          <a href="{{ route('doi.queue') }}" class="btn btn-outline-secondary w-100">
            <i class="fas fa-tasks me-1"></i> View Queue
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection
