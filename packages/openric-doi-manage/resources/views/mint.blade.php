@extends('theme::layouts.1col')

@section('title', 'Mint DOI')
@section('body-class', 'admin doi mint')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-fingerprint me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column flex-grow-1">
      <h1 class="mb-0">Mint DOI</h1>
      <span class="small text-muted">Mint a new DOI for a single entity</span>
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
          <i class="fas fa-stamp me-2"></i>Mint a New DOI
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('doi.mint') }}">
            @csrf

            <div class="mb-3">
              <label for="entity_iri" class="form-label">
                Entity IRI <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="entity_iri" name="entity_iri"
                     value="{{ old('entity_iri') }}"
                     placeholder="e.g. https://example.org/rico/record/123"
                     required>
              <div class="form-text">The full IRI of the archival entity to assign a DOI to</div>
            </div>

            <div class="mb-3">
              <label for="title" class="form-label">
                Title <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="title" name="title"
                     value="{{ old('title') }}"
                     placeholder="Descriptive title for the entity"
                     required>
              <div class="form-text">This title is submitted to DataCite as the primary title</div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-success">
                <i class="fas fa-stamp me-1"></i> Mint DOI
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
      <div class="card">
        <div class="card-header fw-bold bg-primary text-white">
          <i class="fas fa-info-circle me-2"></i>Information
        </div>
        <div class="card-body">
          <p class="small text-muted">
            Minting a DOI will register the identifier with DataCite and make it
            globally resolvable. Once minted, a DOI cannot be deleted -- only
            deactivated (set to "registered" state).
          </p>
          <p class="small text-muted mb-0">
            For minting multiple DOIs at once, use
            <a href="{{ route('doi.batch-mint') }}">Batch Mint</a>.
          </p>
        </div>
      </div>
    </div>
  </div>
@endsection
