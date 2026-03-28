@extends('theme::layouts.1col')

@section('title', 'Graph Overview')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Graph Overview</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('graph.agent-network') }}" class="btn btn-outline-secondary btn-sm">Agent Network</a>
            <a href="{{ route('graph.timeline') }}" class="btn btn-outline-secondary btn-sm">Timeline</a>
        </div>
    </div>
    <p class="text-muted">All Record Sets and their relationships visualised as a graph.</p>

    <div class="card">
        <div class="card-body p-0">
            <div id="cy" style="width:100%; height:700px; border-radius: 0.375rem;"></div>
        </div>
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
            { selector: 'node', style: { 'background-color': 'data(color)', 'label': 'data(label)', 'font-size': '10px', 'text-valign': 'bottom', 'text-margin-y': '5px', 'width': '25px', 'height': '25px', 'text-wrap': 'ellipsis', 'text-max-width': '100px' } },
            { selector: 'edge', style: { 'width': 1, 'line-color': '#ddd', 'target-arrow-color': '#ddd', 'target-arrow-shape': 'triangle', 'curve-style': 'bezier', 'label': 'data(label)', 'font-size': '8px', 'text-rotation': 'autorotate', 'color': '#bbb' } }
        ],
        layout: { name: 'cose', animate: true, nodeRepulsion: function() { return 10000; }, idealEdgeLength: function() { return 150; }, padding: 40 }
    });

    cy.on('tap', 'node', function(evt) { window.location.href = '/graph/entity/' + encodeURIComponent(evt.target.data('id')); });
});
</script>
@endpush
