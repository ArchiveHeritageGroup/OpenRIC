{{-- Block: Holdings List -- adapted from Heratio ahg-landing-page --}}
@php
$items = $data ?? [];
$title = $config['title'] ?? 'Our Holdings';
$showLevel = $config['show_level'] ?? true;
$showDates = $config['show_dates'] ?? true;
$showExtent = $config['show_extent'] ?? false;
$showHits = $config['show_hits'] ?? false;
@endphp
@if (!empty($title))
  <h2 class="h5 mb-3">{{ e($title) }}</h2>
@endif
@if (empty($items))
  <p class="text-muted">No holdings available.</p>
@else
  <ul class="list-group list-group-flush">
    @foreach ($items as $item)
      @php
      $itemObj = is_object($item) ? $item : (object) $item;
      $slug = $itemObj->slug ?? '';
      $itemTitle = $itemObj->title ?? $slug;
      @endphp
      <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
        <div class="text-truncate me-2">
          <a href="{{ url('/record-resource/' . $slug) }}" class="text-decoration-none">
            {{ e($itemTitle) }}
          </a>
          @if ($showLevel && !empty($itemObj->level_of_description ?? null))
            <span class="badge bg-secondary ms-1">{{ e($itemObj->level_of_description) }}</span>
          @endif
        </div>
        <div class="text-nowrap">
          @if ($showDates && !empty($itemObj->date_range ?? null))
            <small class="text-muted me-2">{{ e($itemObj->date_range) }}</small>
          @endif
          @if ($showExtent && !empty($itemObj->extent ?? null))
            <small class="text-muted me-2">{{ e($itemObj->extent) }}</small>
          @endif
          @if ($showHits && isset($itemObj->hits))
            <small class="text-muted">{{ number_format($itemObj->hits) }} visits</small>
          @endif
        </div>
      </li>
    @endforeach
  </ul>
@endif
