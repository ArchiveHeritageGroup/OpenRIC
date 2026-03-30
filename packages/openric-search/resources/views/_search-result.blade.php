{{-- Single search result partial --}}
@php $result = $result ?? $hit ?? []; @endphp
<div class="list-group-item">
  <div class="d-flex align-items-start">
    @if(!empty($result['hasDigitalObject']))
      <span class="text-primary me-2 mt-1" title="Has digital object"><i class="fas fa-file-image" aria-hidden="true"></i></span>
    @endif
    <div class="flex-grow-1">
      <h5 class="mb-1">
        <a href="/{{ $result['slug'] ?? '' }}">{!! $result['highlighted_title'] ?? e($result['title'] ?? '[Untitled]') !!}</a>
      </h5>
      <div class="d-flex flex-wrap gap-2 mb-1">
        @if(!empty($result['identifier']))
          <small class="text-muted"><i class="fas fa-barcode"></i> {{ $result['identifier'] }}</small>
        @endif
        @if(!empty($result['levelName']))
          <small class="text-muted"><i class="fas fa-layer-group"></i> {{ $result['levelName'] }}</small>
        @endif
        @if(!empty($result['repositoryName']))
          <small class="text-muted"><i class="fas fa-institution"></i> {{ $result['repositoryName'] }}</small>
        @endif
        @if(!empty($result['dates']))
          <small class="text-muted"><i class="fas fa-calendar"></i> {{ $result['dates'] }}</small>
        @endif
      </div>
      @if(!empty($result['snippet']))
        <p class="mb-0 mt-1 text-muted small">{!! $result['snippet'] !!}</p>
      @endif
    </div>
  </div>
</div>
