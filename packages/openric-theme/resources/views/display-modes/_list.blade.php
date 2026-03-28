{{-- List display mode — adapted from Heratio _list.blade.php (65 lines) --}}
{{-- Renders browse results as a vertical list with thumbnails and metadata --}}

@php
    $module = $module ?? 'records';
@endphp

<div class="display-list-view" data-display-container data-display-mode="list">
    @forelse($items as $item)
        <div class="result-item browse-item border-bottom py-3" role="article">
            <div class="d-flex align-items-start">
                {{-- Thumbnail --}}
                @if(!empty($item['thumbnail']))
                    <div class="item-thumbnail me-3 flex-shrink-0">
                        <a href="{{ $item['url'] ?? '#' }}">
                            <img src="{{ $item['thumbnail'] }}"
                                 alt="{{ $item['title'] ?? '' }}"
                                 class="rounded"
                                 style="width: 80px; height: 80px; object-fit: cover;"
                                 loading="lazy">
                        </a>
                    </div>
                @else
                    <div class="item-thumbnail me-3 flex-shrink-0 d-flex align-items-center justify-content-center bg-light rounded"
                         style="width: 80px; height: 80px;">
                        <i class="bi {{ $item['icon'] ?? 'bi-file-earmark-text' }} fs-3 text-muted"></i>
                    </div>
                @endif

                {{-- Content --}}
                <div class="item-content flex-grow-1">
                    <h3 class="item-title h6 mb-1">
                        <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none">
                            {{ $item['title'] ?? 'Untitled' }}
                        </a>
                    </h3>

                    {{-- Metadata row --}}
                    <div class="item-meta text-muted small mb-1">
                        @if(!empty($item['reference_code']))
                            <span class="me-3">
                                <i class="bi bi-hash"></i> {{ $item['reference_code'] }}
                            </span>
                        @endif
                        @if(!empty($item['dates']))
                            <span class="me-3">
                                <i class="bi bi-calendar-event"></i> {{ $item['dates'] }}
                            </span>
                        @endif
                        @if(!empty($item['level_of_description']))
                            <span class="me-3">
                                <i class="bi bi-layers"></i> {{ $item['level_of_description'] }}
                            </span>
                        @endif
                        @if(!empty($item['entity_type']))
                            <span class="badge bg-secondary">{{ $item['entity_type'] }}</span>
                        @endif
                    </div>

                    {{-- Description excerpt --}}
                    @if(!empty($item['scope_and_content']))
                        <p class="item-description text-muted small mb-0">
                            {{ Str::limit(strip_tags($item['scope_and_content']), 200) }}
                        </p>
                    @endif
                </div>

                {{-- Security badge --}}
                @if(!empty($item['classification']))
                    <div class="ms-2 flex-shrink-0">
                        <span class="badge" style="background-color: {{ $item['classification_color'] ?? '#6c757d' }}">
                            {{ $item['classification'] }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            <p>No results found.</p>
        </div>
    @endforelse
</div>
