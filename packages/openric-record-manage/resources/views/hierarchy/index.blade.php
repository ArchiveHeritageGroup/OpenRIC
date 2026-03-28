@extends('theme::layouts.2col')

@section('title', 'Hierarchical Browse')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">Hierarchical Browse</h1>
    <p class="text-muted">Browse the archival hierarchy from fonds down to item level.</p>

    @include('theme::partials.alerts')

    <div id="hierarchy-tree">
        @if(count($roots) > 0)
            <ul class="list-group">
                @foreach($roots as $root)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary me-1">Fonds</span>
                                <a href="{{ route('record-sets.show', ['iri' => urlencode($root['iri']['value'] ?? '')]) }}">
                                    {{ $root['title']['value'] ?? 'Untitled' }}
                                </a>
                                @if(!empty($root['identifier']['value']))
                                    <span class="text-muted small ms-2">({{ $root['identifier']['value'] }})</span>
                                @endif
                            </div>
                            @if(($root['childCount']['value'] ?? 0) > 0)
                                <a href="{{ route('hierarchy.tree', ['iri' => urlencode($root['iri']['value'] ?? '')]) }}" class="btn btn-sm btn-outline-secondary">
                                    Expand ({{ $root['childCount']['value'] }})
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="alert alert-info">No archival fonds found in the triplestore. Create a Record Set to begin.</div>
        @endif
    </div>
@endsection
