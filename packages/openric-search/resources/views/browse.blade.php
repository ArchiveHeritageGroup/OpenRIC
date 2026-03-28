@extends('theme::layouts.2col')

@section('title', 'Browse')

@section('sidebar')
    <nav aria-label="Browse facets">
        <h5 class="mb-3">Filter</h5>

        {{-- Entity type facet --}}
        <div class="mb-4">
            <h6 class="text-uppercase small text-muted">Entity Type</h6>
            <div class="list-group list-group-flush">
                @foreach($facets['entity_types'] ?? [] as $facet)
                    <a href="{{ request()->fullUrlWithQuery(['entity_type' => $facet['value'], 'page' => 1]) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 {{ ($params['entity_type'] ?? '') === $facet['value'] ? 'active' : '' }}">
                        {{ $facet['value'] }}
                        <span class="badge bg-secondary rounded-pill">{{ $facet['count'] }}</span>
                    </a>
                @endforeach
                @if(!empty($params['entity_type']))
                    <a href="{{ request()->fullUrlWithQuery(['entity_type' => null, 'page' => 1]) }}"
                       class="list-group-item list-group-item-action text-danger py-1">Clear filter</a>
                @endif
            </div>
        </div>

        {{-- Creator facet --}}
        @if(count($facets['creators'] ?? []) > 0)
            <div class="mb-4">
                <h6 class="text-uppercase small text-muted">Creator</h6>
                <div class="list-group list-group-flush">
                    @foreach(array_slice($facets['creators'], 0, 10) as $facet)
                        <a href="{{ request()->fullUrlWithQuery(['creator' => $facet['iri'], 'page' => 1]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 {{ ($params['creator'] ?? '') === $facet['iri'] ? 'active' : '' }}">
                            {{ \Illuminate\Support\Str::limit($facet['name'], 30) }}
                            <span class="badge bg-secondary rounded-pill">{{ $facet['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Search within browse --}}
        <div class="mb-4">
            <h6 class="text-uppercase small text-muted">Search</h6>
            <form method="GET" action="{{ route('browse') }}">
                @foreach($params as $k => $v)
                    @if($k !== 'subquery' && $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" name="subquery" value="{{ $params['subquery'] ?? '' }}" placeholder="Filter...">
                    <button class="btn btn-outline-secondary" type="submit">Go</button>
                </div>
            </form>
        </div>
    </nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Browse <span class="text-muted">({{ $total }})</span></h1>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width:auto;" onchange="window.location=this.value">
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'title', 'sortDir' => 'ASC']) }}" {{ ($params['sort'] ?? '') === 'title' ? 'selected' : '' }}>Title A-Z</option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'title', 'sortDir' => 'DESC']) }}" {{ ($params['sort'] ?? '') === 'title' && ($params['sortDir'] ?? '') === 'DESC' ? 'selected' : '' }}>Title Z-A</option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'date', 'sortDir' => 'DESC']) }}" {{ ($params['sort'] ?? '') === 'date' ? 'selected' : '' }}>Date (newest)</option>
            </select>
        </div>
    </div>

    @include('theme::partials.alerts')

    @forelse($items as $item)
        @php
            $typeStr = str_replace('https://www.ica.org/standards/RiC/ontology#', '', $item['type']['value'] ?? '');
            $typeColors = ['RecordSet' => 'primary', 'Record' => 'info', 'Person' => 'success', 'CorporateBody' => 'success', 'Family' => 'success', 'Activity' => 'warning', 'Place' => 'warning', 'Mandate' => 'danger', 'Instantiation' => 'secondary'];
            $badge = $typeColors[$typeStr] ?? 'secondary';
        @endphp
        <div class="card mb-2">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-{{ $badge }} me-1">{{ $typeStr }}</span>
                        <a href="{{ route('record-sets.show', ['iri' => urlencode($item['iri']['value'] ?? '')]) }}" class="fw-bold">
                            {{ $item['title']['value'] ?? 'Untitled' }}
                        </a>
                        @if(!empty($item['identifier']['value']))
                            <span class="text-muted small ms-2">({{ $item['identifier']['value'] }})</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No entities found matching your criteria.</div>
    @endforelse

    @if($total > $limit)
        <nav aria-label="Browse pagination" class="mt-3">
            <ul class="pagination">
                @if($page > 1)
                    <li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">Previous</a></li>
                @endif
                <li class="page-item disabled"><span class="page-link">Page {{ $page }} of {{ ceil($total / $limit) }}</span></li>
                @if($page * $limit < $total)
                    <li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">Next</a></li>
                @endif
            </ul>
        </nav>
    @endif
@endsection
