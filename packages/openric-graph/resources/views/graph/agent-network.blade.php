@extends('theme::layouts.1col')

@section('title', 'Agent Network')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Agent Network</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('graph.overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
            <a href="{{ route('graph.timeline') }}" class="btn btn-outline-secondary btn-sm">Timeline</a>
        </div>
    </div>
    <p class="text-muted">Who created what — agents connected to records via <code>rico:hasOrHadCreator</code>.</p>

    <div class="card">
        <div class="card-body p-0">
            <div id="cy" style="width:100%; height:700px; border-radius: 0.375rem;"></div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <span class="badge" style="background:#0dcaf0;color:#000;">Record</span>
        <span class="badge" style="background:#198754;">Person</span>
        <span class="badge" style="background:#20c997;color:#000;">CorporateBody</span>
        <span class="badge" style="background:#6ea8fe;">Family</span>
    </div>
@endsection

@push('js')
<script src="https://unpkg.com/cytoscape@3.28.1/dist/cytoscape.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var graphData = @json($graphData);
    var elements = [];
    graphData.nodes.forEach(function(n) { elements.push({ data: n.data }); });
    graphData.edges.forEach(function(e) { elements.push({ data: e.data }); });

    var cy = cytoscape({
        container: document.getElementById('cy'),
        elements: elements,
        style: [
            { selector: 'node', style: { 'background-color': 'data(color)', 'label': 'data(label)', 'font-size': '11px', 'text-valign': 'bottom', 'text-margin-y': '5px', 'width': '30px', 'height': '30px', 'text-wrap': 'ellipsis', 'text-max-width': '120px' } },
            { selector: 'edge', style: { 'width': 1.5, 'line-color': '#ccc', 'target-arrow-color': '#ccc', 'target-arrow-shape': 'triangle', 'curve-style': 'bezier', 'label': 'data(label)', 'font-size': '9px', 'text-rotation': 'autorotate', 'color': '#999' } }
        ],
        layout: { name: 'cose', animate: true, nodeRepulsion: function() { return 8000; }, idealEdgeLength: function() { return 100; }, padding: 30 }
    });

    cy.on('tap', 'node', function(evt) { window.location.href = '/graph/entity/' + encodeURIComponent(evt.target.data('id')); });
});
</script>
@endpush
