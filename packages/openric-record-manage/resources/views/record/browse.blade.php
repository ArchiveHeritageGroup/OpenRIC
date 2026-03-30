@extends('theme::layouts.2col')

@section('title', 'Browse Records')

@section('sidebar')
    <h5>Narrow your results by:</h5>

    @foreach($facets ?? [] as $key => $facet)
        @if(!empty($facet['terms']))
            <div class="accordion mb-3">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $key }}">
                            {{ $facet['label'] }}
                        </button>
                    </h2>
                    <div id="collapse-{{ $key }}" class="accordion-collapse collapse list-group list-group-flush">
                        @foreach($facet['terms'] as $term)
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                               href="{{ request()->fullUrlWithQuery([$key => $term['id'], 'page' => 1]) }}">
                                {{ $term['label'] }}
                                <span class="badge bg-secondary">{{ $term['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            @if($total > 0)
                Showing {{ number_format($total) }} results
            @else
                No results found
            @endif
        </h1>
    </div>

    @include('theme::partials.alerts')

    @if(!empty($items))
        <div class="list-group mb-3">
            @foreach($items as $item)
                <a href="{{ route('records.show', ['iri' => urlencode($item['iri'] ?? '')]) }}"
                   class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $item['title'] ?: '[Untitled]' }}</h5>
                        <small>{{ $item['updated_at'] ?? '' }}</small>
                    </div>
                    @if(!empty($item['identifier']))
                        <small class="text-muted">{{ $item['identifier'] }}</small>
                    @endif
                    @if(!empty($item['level']))
                        <span class="badge bg-info ms-2">{{ $item['level'] }}</span>
                    @endif
                    @if(!empty($item['scope_and_content']))
                        <p class="mb-1 small text-muted">{{ $item['scope_and_content'] }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        @include('theme::partials.pagination', ['page' => $page, 'totalPages' => $totalPages])
    @endif
@endsection
