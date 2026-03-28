{{-- Grid display mode — adapted from Heratio _grid.blade.php (62 lines) --}}
{{-- Renders browse results as a compact card grid --}}

@php
    $module = $module ?? 'records';
@endphp

<div class="display-grid-view" data-display-container data-display-mode="grid">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        @forelse($items as $item)
            <div class="col" role="article">
                <div class="card h-100 border">
                    {{-- Card image --}}
                    @if(!empty($item['thumbnail']))
                        <img src="{{ $item['thumbnail'] }}"
                             class="card-img-top"
                             alt="{{ $item['title'] ?? '' }}"
                             style="height: 120px; object-fit: cover;"
                             loading="lazy">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                             style="height: 120px;">
                            <i class="bi {{ $item['icon'] ?? 'bi-file-earmark' }} fs-2 text-muted"></i>
                        </div>
                    @endif

                    {{-- Card body --}}
                    <div class="card-body p-2">
                        <h6 class="card-title small mb-1">
                            <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none stretched-link">
                                {{ Str::limit($item['title'] ?? 'Untitled', 40) }}
                            </a>
                        </h6>
                        @if(!empty($item['reference_code']))
                            <p class="card-text text-muted" style="font-size: 0.75rem;">
                                {{ $item['reference_code'] }}
                            </p>
                        @endif
                        @if(!empty($item['dates']))
                            <p class="card-text text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-calendar-event"></i> {{ $item['dates'] }}
                            </p>
                        @endif
                    </div>

                    {{-- Footer --}}
                    @if(!empty($item['level_of_description']))
                        <div class="card-footer bg-transparent p-2" style="font-size: 0.7rem;">
                            <i class="bi bi-layers"></i> {{ $item['level_of_description'] }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-grid-3x3 fs-1 d-block mb-2"></i>
                <p>No results found.</p>
            </div>
        @endforelse
    </div>
</div>
