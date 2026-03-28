{{-- Timeline display mode — adapted from Heratio _timeline.blade.php (79 lines) --}}
{{-- Groups items by year and renders as a vertical timeline --}}

@php
    $module = $module ?? 'records';

    // Group items by year — extract from dates field or start_date
    $grouped = collect($items)->groupBy(function ($item) {
        if (!empty($item['start_date'])) {
            return substr($item['start_date'], 0, 4);
        }
        if (!empty($item['dates']) && preg_match('/\b(\d{4})\b/', $item['dates'], $m)) {
            return $m[1];
        }
        return 'Unknown';
    })->sortKeysDesc();
@endphp

<div class="display-timeline-view" data-display-container data-display-mode="timeline">
    @forelse($grouped as $year => $yearItems)
        <div class="timeline-year mb-4">
            {{-- Year marker --}}
            <div class="d-flex align-items-center mb-3">
                <div class="timeline-marker bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                     style="width: 60px; height: 60px; flex-shrink: 0;">
                    {{ $year }}
                </div>
                <hr class="flex-grow-1 ms-3">
                <span class="badge bg-secondary ms-2">{{ count($yearItems) }} {{ Str::plural('item', count($yearItems)) }}</span>
            </div>

            {{-- Items for this year --}}
            <div class="timeline-items ps-4 border-start border-primary border-2 ms-4">
                @foreach($yearItems as $item)
                    <div class="timeline-item mb-3 ps-3 position-relative" role="article">
                        {{-- Dot on the timeline --}}
                        <div class="position-absolute bg-primary rounded-circle"
                             style="width: 12px; height: 12px; left: -8px; top: 6px;"></div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-1">
                                    <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none">
                                        {{ $item['title'] ?? 'Untitled' }}
                                    </a>
                                </h6>
                                <div class="text-muted small">
                                    @if(!empty($item['dates']))
                                        <span class="me-2"><i class="bi bi-calendar-event"></i> {{ $item['dates'] }}</span>
                                    @endif
                                    @if(!empty($item['reference_code']))
                                        <span class="me-2"><i class="bi bi-hash"></i> {{ $item['reference_code'] }}</span>
                                    @endif
                                    @if(!empty($item['level_of_description']))
                                        <span><i class="bi bi-layers"></i> {{ $item['level_of_description'] }}</span>
                                    @endif
                                </div>
                                @if(!empty($item['scope_and_content']))
                                    <p class="small text-muted mt-1 mb-0">
                                        {{ Str::limit(strip_tags($item['scope_and_content']), 150) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
            <p>No results found.</p>
        </div>
    @endforelse
</div>
