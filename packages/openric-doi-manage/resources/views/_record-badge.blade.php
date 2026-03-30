{{-- Partial: DOI badge for embedding in entity views --}}
@props(['doi' => null, 'doiUrl' => null])

@if($doi)
  <a href="{{ $doiUrl ?? 'https://doi.org/' . $doi }}" target="_blank"
     class="badge bg-info text-decoration-none" title="DOI: {{ $doi }}">
    <i class="fas fa-link me-1"></i>{{ $doi }}
  </a>
@endif
