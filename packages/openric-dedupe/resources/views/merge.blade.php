@extends('theme::layouts.1col')

@section('title', 'Merge Duplicate Records')
@section('body-class', 'admin dedupe merge')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-compress-arrows-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Merge Duplicate Records</h1>
      <span class="small text-muted">Duplicate Detection</span>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('dedupe.compare', $candidate['id']) }}" class="btn atom-btn-white">
        <i class="fas fa-columns me-1"></i> Back to Compare
      </a>
    </div>
  </div>

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Warning:</strong> Merging records is permanent. The secondary entity will be deprecated and its relationships transferred to the primary entity.
  </div>

  <form method="post" action="{{ route('dedupe.merge', $candidate['id']) }}" id="mergeForm">
    @csrf

    {{-- Detection Info --}}
    <div class="card mb-4">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
        <h5 class="mb-0">Detection Details</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <strong>Similarity Score:</strong>
            @php $score = round(($candidate['similarity_score'] ?? 0) * 100); @endphp
            <span class="badge {{ $score >= 90 ? 'bg-danger' : ($score >= 75 ? 'bg-warning text-dark' : 'bg-info text-dark') }}">
              {{ number_format($score, 1) }}%
            </span>
          </div>
          <div class="col-md-4">
            <strong>Entity Type:</strong>
            {{ $candidate['entity_type'] ?? 'Record' }}
          </div>
          <div class="col-md-4">
            <strong>Detected:</strong>
            {{ isset($candidate['created_at']) ? \Carbon\Carbon::parse($candidate['created_at'])->format('M j, Y H:i') : '-' }}
          </div>
        </div>
      </div>
    </div>

    {{-- Select Primary --}}
    <div class="card mb-4">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Step 1: Select Canonical Record</h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4">The canonical record will be kept. The other entity's relationships will be transferred to it.</p>

        <div class="row">
          <div class="col-md-6">
            <div class="card h-100 border-2 primary-option" id="optionA">
              <div class="card-header bg-light">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="canonical_iri"
                         value="{{ $entityA['iri'] ?? '' }}" id="primaryA" checked>
                  <label class="form-check-label fw-bold" for="primaryA">
                    Entity A (Keep This)
                  </label>
                </div>
              </div>
              <div class="card-body">
                <h5>{{ $entityA['rico:title'] ?? $entityA['title'] ?? 'Untitled' }}</h5>
                <p class="text-muted mb-2"><strong>Identifier:</strong> {{ $entityA['rico:identifier'] ?? 'N/A' }}</p>
                <p class="text-muted mb-0 text-break"><strong>IRI:</strong> {{ $entityA['iri'] ?? '' }}</p>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card h-100 border-2 primary-option" id="optionB">
              <div class="card-header bg-light">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="canonical_iri"
                         value="{{ $entityB['iri'] ?? '' }}" id="primaryB">
                  <label class="form-check-label fw-bold" for="primaryB">
                    Entity B (Keep This)
                  </label>
                </div>
              </div>
              <div class="card-body">
                <h5>{{ $entityB['rico:title'] ?? $entityB['title'] ?? 'Untitled' }}</h5>
                <p class="text-muted mb-2"><strong>Identifier:</strong> {{ $entityB['rico:identifier'] ?? 'N/A' }}</p>
                <p class="text-muted mb-0 text-break"><strong>IRI:</strong> {{ $entityB['iri'] ?? '' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Merge Actions --}}
    <div class="card mb-4">
      <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Step 2: Review Merge Actions</h5>
      </div>
      <div class="card-body">
        <p>The following actions will be performed:</p>
        <ul class="list-group list-group-flush">
          <li class="list-group-item"><i class="fas fa-link me-2 text-primary"></i>All relationships from the duplicate entity will be transferred to the canonical entity</li>
          <li class="list-group-item"><i class="fas fa-exchange-alt me-2 text-primary"></i>An owl:sameAs link will be created from the duplicate to the canonical entity</li>
          <li class="list-group-item"><i class="fas fa-archive me-2 text-primary"></i>The duplicate entity will be marked as deprecated</li>
          <li class="list-group-item"><i class="fas fa-history me-2 text-primary"></i>A merge log entry will be created for audit purposes</li>
        </ul>
      </div>
    </div>

    {{-- Confirmation --}}
    <div class="card mb-4">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Step 3: Confirm Merge</h5>
      </div>
      <div class="card-body">
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="confirmMerge" required>
          <label class="form-check-label" for="confirmMerge">
            I understand that this action is permanent and cannot be undone.
          </label>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn atom-btn-outline-danger" id="mergeBtn" disabled>
            <i class="fas fa-compress-arrows-alt me-1"></i> Merge Records
          </button>
          <a href="{{ route('dedupe.compare', $candidate['id']) }}" class="btn atom-btn-white">
            <i class="fas fa-columns me-1"></i> Back to Compare
          </a>
          <a href="{{ route('dedupe.records') }}" class="btn atom-btn-white">Cancel</a>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('styles')
<style>
.primary-option { cursor: pointer; transition: all 0.2s; }
.primary-option:hover { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.primary-option.selected { border-color: #0d6efd !important; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25); }
</style>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var confirmCheck = document.getElementById('confirmMerge');
    var mergeBtn = document.getElementById('mergeBtn');
    var optionA = document.getElementById('optionA');
    var optionB = document.getElementById('optionB');
    var radioA = document.getElementById('primaryA');
    var radioB = document.getElementById('primaryB');

    confirmCheck.addEventListener('change', function() { mergeBtn.disabled = !this.checked; });

    function updateSelection() {
        optionA.classList.toggle('selected', radioA.checked);
        optionB.classList.toggle('selected', radioB.checked);
    }

    radioA.addEventListener('change', updateSelection);
    radioB.addEventListener('change', updateSelection);
    optionA.addEventListener('click', function(e) { if (e.target.tagName !== 'A') { radioA.checked = true; updateSelection(); } });
    optionB.addEventListener('click', function(e) { if (e.target.tagName !== 'A') { radioB.checked = true; updateSelection(); } });
    updateSelection();
});
</script>
@endpush
