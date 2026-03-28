<div class="btn-group btn-group-sm" role="group" aria-label="View mode">
    @php $viewMode = session('openric_view_mode', config('openric.default_view', 'ric')); @endphp
    <a href="{{ request()->fullUrlWithQuery(['view' => 'ric']) }}"
       class="btn {{ $viewMode === 'ric' ? 'btn-info' : 'btn-outline-info' }}"
       title="RiC-O native view">
        RiC
    </a>
    <a href="{{ request()->fullUrlWithQuery(['view' => 'traditional']) }}"
       class="btn {{ $viewMode === 'traditional' ? 'btn-info' : 'btn-outline-info' }}"
       title="Traditional archival view (ISAD(G) / ISAAR-CPF)">
        Traditional
    </a>
    @if(isset($entity['iri']) || isset($iri))
        <a href="{{ route('graph.entity', ['iri' => urlencode($entity['iri'] ?? $iri ?? '')]) }}"
           class="btn {{ $viewMode === 'graph' ? 'btn-info' : 'btn-outline-info' }}"
           title="Graph visualisation">
            Graph
        </a>
    @endif
</div>
