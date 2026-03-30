{{-- Facet aggregation partial --}}
@if(!isset($buckets) || count($buckets) < 1)
  @php return; @endphp
@endif

@php $opened = request()->has($paramName ?? '') || (!empty($buckets) && ($open ?? false)); @endphp

<div class="accordion mb-3">
  <div class="accordion-item">
    <h2 class="accordion-header" id="heading-{{ $paramName ?? 'facet' }}">
      <button class="accordion-button{{ $opened ? '' : ' collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $paramName ?? 'facet' }}" aria-expanded="{{ $opened ? 'true' : 'false' }}">
        {{ $label ?? 'Filter' }}
      </button>
    </h2>
    <div id="collapse-{{ $paramName ?? 'facet' }}" class="accordion-collapse collapse{{ $opened ? ' show' : '' }} list-group list-group-flush">
      <a href="{{ request()->fullUrlWithQuery([$paramName => null, 'page' => null]) }}" class="list-group-item list-group-item-action d-flex justify-content-between{{ !request()->has($paramName) ? ' active' : '' }}">
        All
      </a>
      @foreach($buckets as $bucket)
        @php $active = request($paramName) == ($bucket['id'] ?? $bucket['key'] ?? ''); @endphp
        <a href="{{ request()->fullUrlWithQuery(['page' => null, $paramName => $bucket['id'] ?? $bucket['key'] ?? '']) }}"
           class="list-group-item list-group-item-action d-flex justify-content-between{{ $active ? ' active' : '' }}">
          {{ $bucket['label'] ?? $bucket['display'] ?? '' }}
          <span class="badge bg-secondary rounded-pill ms-2">{{ $bucket['count'] ?? $bucket['doc_count'] ?? 0 }}</span>
        </a>
      @endforeach
    </div>
  </div>
</div>
