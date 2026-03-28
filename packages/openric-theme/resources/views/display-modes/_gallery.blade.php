{{-- Gallery display mode — adapted from Heratio _gallery.blade.php (56 lines) --}}
{{-- Renders browse results as an image gallery grid with lightbox support --}}

@php
    $module = $module ?? 'records';
@endphp

<div class="display-gallery-view" data-display-container data-display-mode="gallery">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse($items as $item)
            <div class="col" role="article">
                <div class="card h-100 shadow-sm">
                    {{-- Card image --}}
                    <a href="{{ $item['url'] ?? '#' }}"
                       @if(!empty($item['thumbnail_large']))
                           data-lightbox="gallery"
                           data-title="{{ $item['title'] ?? '' }}"
                       @endif
                    >
                        @if(!empty($item['thumbnail_large'] ?? $item['thumbnail']))
                            <img src="{{ $item['thumbnail_large'] ?? $item['thumbnail'] }}"
                                 class="card-img-top"
                                 alt="{{ $item['title'] ?? '' }}"
                                 style="height: 200px; object-fit: cover;"
                                 loading="lazy">
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="height: 200px;">
                                <i class="bi {{ $item['icon'] ?? 'bi-image' }} fs-1 text-muted"></i>
                            </div>
                        @endif
                    </a>

                    {{-- Card body --}}
                    <div class="card-body">
                        <h5 class="card-title h6">
                            <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none stretched-link">
                                {{ Str::limit($item['title'] ?? 'Untitled', 60) }}
                            </a>
                        </h5>
                        @if(!empty($item['dates']))
                            <p class="card-text text-muted small">
                                <i class="bi bi-calendar-event"></i> {{ $item['dates'] }}
                            </p>
                        @endif
                    </div>

                    {{-- Card footer --}}
                    @if(!empty($item['level_of_description']) || !empty($item['entity_type']))
                        <div class="card-footer bg-transparent border-top-0 text-muted small">
                            @if(!empty($item['entity_type']))
                                <span class="badge bg-secondary">{{ $item['entity_type'] }}</span>
                            @endif
                            @if(!empty($item['level_of_description']))
                                <i class="bi bi-layers"></i> {{ $item['level_of_description'] }}
                            @endif
                        </div>
                    @endif

                    {{-- Classification overlay --}}
                    @if(!empty($item['classification']))
                        <span class="position-absolute top-0 end-0 m-2 badge"
                              style="background-color: {{ $item['classification_color'] ?? '#6c757d' }}">
                            {{ $item['classification'] }}
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-images fs-1 d-block mb-2"></i>
                <p>No results found.</p>
            </div>
        @endforelse
    </div>
</div>
