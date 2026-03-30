{{-- Semantic Search Expansion Info Partial --}}
@php
if (!isset($expansionInfo) || !($expansionInfo['expanded'] ?? false)) { return; }
@endphp

<div class="semantic-expansion-info alert alert-info alert-dismissible fade show mb-3" role="alert">
  <div class="d-flex align-items-start">
    <i class="fas fa-brain fa-lg me-3 mt-1 text-info" aria-hidden="true"></i>
    <div class="flex-grow-1">
      <strong>Semantic Search Active</strong>
      <p class="mb-1 small">Your search has been expanded with related terms:</p>
      @if(!empty($expansionInfo['terms']))
        <div class="expansion-terms">
          @foreach($expansionInfo['terms'] as $originalTerm => $synonyms)
            <div class="term-expansion mb-1">
              <code class="bg-light px-1 rounded">{{ $originalTerm }}</code>
              <i class="fas fa-arrow-right mx-2 text-muted small"></i>
              @foreach($synonyms as $syn)
                <span class="badge bg-secondary">{{ $syn }}</span>
              @endforeach
            </div>
          @endforeach
        </div>
      @endif
      <p class="mb-0 mt-2 small text-muted"><i class="fas fa-info-circle me-1"></i> Disable semantic search to search for exact terms only.</p>
    </div>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
