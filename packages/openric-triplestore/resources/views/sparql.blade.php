@extends('theme::layouts.1col')

@section('title', 'SPARQL Endpoint')

@section('content')
    <h1 class="h3 mb-3">SPARQL Endpoint</h1>
    <p class="text-muted">Public read-only SPARQL 1.1 endpoint. Query the OpenRiC RiC-O triplestore directly.</p>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('sparql.query') }}" id="sparql-form">
                @csrf
                <div class="mb-3">
                    <label for="query" class="form-label">SPARQL Query</label>
                    <textarea class="form-control font-monospace" id="query" name="query" rows="12" style="tab-size:4;">{{ $prefixes }}

SELECT ?s ?p ?o
WHERE {
    ?s ?p ?o .
}
LIMIT 25</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Execute</button>
                    <a href="{{ route('sparql.prefixes') }}" class="btn btn-outline-secondary" target="_blank">Prefixes (JSON)</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Endpoint Information</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Query URL</dt>
                <dd class="col-sm-9"><code>{{ url('/sparql') }}</code> (GET with <code>?query=</code> or POST)</dd>
                <dt class="col-sm-3">Supported Operations</dt>
                <dd class="col-sm-9">SELECT, ASK, CONSTRUCT, DESCRIBE (read-only)</dd>
                <dt class="col-sm-3">Accept Types</dt>
                <dd class="col-sm-9"><code>application/sparql-results+json</code>, <code>text/turtle</code>, <code>application/rdf+xml</code></dd>
                <dt class="col-sm-3">CORS</dt>
                <dd class="col-sm-9">Enabled (Access-Control-Allow-Origin: *)</dd>
            </dl>
        </div>
    </div>
@endsection
