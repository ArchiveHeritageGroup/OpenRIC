@extends('theme::layouts.1col')

@section('title', 'Graph View')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Graph View</h1>
        <div class="d-flex gap-2">
            @include('theme::partials.view-switch')
            <a href="{{ route('graph.overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
            <a href="{{ route('graph.agent-network') }}" class="btn btn-outline-secondary btn-sm">Agent Network</a>
            <a href="{{ route('graph.timeline') }}" class="btn btn-outline-secondary btn-sm">Timeline</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="cy" style="width:100%; height:600px; border-radius: 0.375rem;"></div>
        </div>
    </div>

    <div class="mt-3">
        <h5>Legend</h5>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge" style="background:#0d6efd;">RecordSet</span>
            <span class="badge" style="background:#0dcaf0;color:#000;">Record</span>
            <span class="badge" style="background:#198754;">Person</span>
            <span class="badge" style="background:#20c997;color:#000;">CorporateBody</span>
            <span class="badge" style="background:#ffc107;color:#000;">Activity</span>
            <span class="badge" style="background:#fd7e14;">Place</span>
            <span class="badge" style="background:#dc3545;">Mandate</span>
            <span class="badge" style="background:#6f42c1;">Instantiation</span>
        </div>
    </div>
@endsection

@push('js')
<script src="https://unpkg.com/cytoscape@3.28.1/dist/cytoscape.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var graphData = @json($graphData);

    var elements = [];

    graphData.nodes.forEach(function(node) {
        elements.push({
            data: {
                id: node.data.id,
                label: node.data.label,
                color: node.data.color || '#666',
                type: node.data.type || 'Entity',
                isCentre: node.data.isCentre || false
            }
        });
    });

    graphData.edges.forEach(function(edge) {
        elements.push({
            data: {
                source: edge.data.source,
                target: edge.data.target,
                label: edge.data.label || ''
            }
        });
    });

    var cy = cytoscape({
        container: document.getElementById('cy'),
        elements: elements,
        style: [
            {
                selector: 'node',
                style: {
                    'background-color': 'data(color)',
                    'label': 'data(label)',
                    'font-size': '11px',
                    'text-valign': 'bottom',
                    'text-halign': 'center',
                    'text-margin-y': '5px',
                    'width': '30px',
                    'height': '30px',
                    'text-wrap': 'ellipsis',
                    'text-max-width': '120px'
                }
            },
            {
                selector: 'node[?isCentre]',
                style: {
                    'width': '50px',
                    'height': '50px',
                    'border-width': '3px',
                    'border-color': '#000',
                    'font-weight': 'bold',
                    'font-size': '13px'
                }
            },
            {
                selector: 'edge',
                style: {
                    'width': 1.5,
                    'line-color': '#ccc',
                    'target-arrow-color': '#ccc',
                    'target-arrow-shape': 'triangle',
                    'curve-style': 'bezier',
                    'label': 'data(label)',
                    'font-size': '9px',
                    'text-rotation': 'autorotate',
                    'color': '#999'
                }
            }
        ],
        layout: {
            name: 'cose',
            animate: true,
            animationDuration: 500,
            nodeRepulsion: function() { return 8000; },
            idealEdgeLength: function() { return 120; },
            padding: 30
        }
    });

    cy.on('tap', 'node', function(evt) {
        var nodeId = evt.target.data('id');
        window.location.href = '/graph/entity/' + encodeURIComponent(nodeId);
    });
});
</script>
@endpush
