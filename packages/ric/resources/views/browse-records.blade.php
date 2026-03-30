@extends('theme::layouts.master')

@section('title', __('Browse Records') . ' — ' . __('OpenRiC'))

@push('styles')
<style>
    .record-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .record-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .badge-level {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
</style>
@endpush

@section('layout-content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/browse') }}">{{ __('Browse') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Archival Descriptions') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-2">
                <i class="fas fa-folder-open me-2 text-primary"></i>{{ __('Archival Descriptions') }}
            </h1>
            <p class="text-muted mb-0">
                {{ __('Showing') }} {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}
                {{ __('of') }} {{ number_format($records->total()) }} {{ __('records') }}
            </p>
        </div>
    </div>

    {{-- Filters & Sorting --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2">
                    <form method="GET" action="{{ url('/browse/records') }}" class="row g-2 align-items-center">
                        {{-- Top-level filter --}}
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="topLod" name="topLod" value="1"
                                    {{ $topLod === '1' ? 'checked' : '' }}
                                    onchange="this.form.submit()">
                                <label class="form-check-label small" for="topLod">
                                    {{ __('Top-level only') }}
                                </label>
                            </div>
                        </div>

                        {{-- Sort --}}
                        <div class="col-auto">
                            <label class="small text-muted">{{ __('Sort by:') }}</label>
                        </div>
                        <div class="col-auto">
                            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>{{ __('Title') }}</option>
                                <option value="identifier" {{ $sort === 'identifier' ? 'selected' : '' }}>{{ __('Identifier') }}</option>
                                <option value="level" {{ $sort === 'level' ? 'selected' : '' }}>{{ __('Level of Description') }}</option>
                                <option value="repository" {{ $sort === 'repository' ? 'selected' : '' }}>{{ __('Repository') }}</option>
                                <option value="updated" {{ $sort === 'updated' ? 'selected' : '' }}>{{ __('Last Updated') }}</option>
                            </select>
                        </div>

                        {{-- Order --}}
                        <div class="col-auto">
                            <select name="order" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="asc" {{ $order === 'asc' ? 'selected' : '' }}>
                                    <i class="fas fa-arrow-up"></i> {{ __('Ascending') }}
                                </option>
                                <option value="desc" {{ $order === 'desc' ? 'selected' : '' }}>
                                    <i class="fas fa-arrow-down"></i> {{ __('Descending') }}
                                </option>
                            </select>
                        </div>

                        {{-- Keep topLod in query --}}
                        @if($topLod === '1')
                            <input type="hidden" name="topLod" value="1">
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Records Grid --}}
    <div class="row g-3">
        @forelse($records as $record)
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card record-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    {{-- Level of Description Badge --}}
                    @if($record->level_name)
                        <span class="badge badge-level bg-secondary mb-2">
                            {{ $record->level_name }}
                        </span>
                    @endif

                    {{-- Title --}}
                    <h5 class="card-title mb-1" style="font-size: 0.95rem; line-height: 1.3;">
                        @if($record->slug)
                            <a href="{{ url('/' . $record->slug) }}" class="text-decoration-none text-dark stretched-link-x">
                                {{ Str::limit($record->title ?: __('Untitled'), 80) }}
                            </a>
                        @else
                            <span class="text-muted">{{ Str::limit($record->title ?: __('Untitled'), 80) }}</span>
                        @endif
                    </h5>

                    {{-- Identifier --}}
                    @if($record->identifier)
                        <p class="card-text small text-muted mb-1">
                            <i class="fas fa-hashtag me-1"></i>{{ $record->identifier }}
                        </p>
                    @endif

                    {{-- Repository --}}
                    @if($record->repository_name)
                        <p class="card-text small text-muted mb-0">
                            <i class="fas fa-building me-1"></i>{{ Str::limit($record->repository_name, 40) }}
                        </p>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <small class="text-muted">
                        @if($record->updated_at)
                            <i class="fas fa-clock me-1"></i>{{ $record->updated_at->diffForHumans() }}
                        @endif
                    </small>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                {{ __('No records found.') }}
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($records->hasPages())
    <div class="row mt-4">
        <div class="col-12">
            <nav aria-label="Records pagination">
                <ul class="pagination justify-content-center">
                    {{-- Previous --}}
                    @if($records->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $records->previousPageUrl() }}">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach($records->getUrlRange(max(1, $records->currentPage() - 2), min($records->lastPage(), $records->currentPage() + 2)) as $page => $url)
                        @if($page == $records->currentPage())
                            <li class="page-item active">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($records->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $records->nextPageUrl() }}">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                        </li>
                    @endif
                </ul>
            </nav>
            <p class="text-center text-muted small mb-0">
                {{ __('Page') }} {{ $records->currentPage() }} {{ __('of') }} {{ $records->lastPage() }}
            </p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Make entire card clickable
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.stretched-link-x').forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Let the natural link behavior work
                e.stopPropagation();
            });
            // Make the card clickable
            const card = link.closest('.card');
            if (card) {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function() {
                    window.location.href = link.href;
                });
            }
        });
    });
</script>
@endpush
