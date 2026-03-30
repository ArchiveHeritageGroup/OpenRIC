@extends('theme::layouts.standalone')

@section('title', 'Graph Explorer')
@section('body-class', 'ric-explorer-standalone')

@push('styles')
<link rel="stylesheet" href="/vendor/openric-ric/css/ric-explorer.css">
<style>
    :root {
        --openric-primary: #1a5276;
        --ahg-primary: var(--openric-primary);
    }
    body.ric-explorer-standalone {
        background: #0d1117;
        color: #e6edf3;
    }
    .ric-page-container {
        min-height: 100vh;
        padding: 20px;
    }
    .ric-header-bar {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border: 1px solid #30363d;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .ric-header-bar h1 {
        color: #58a6ff;
        font-size: 1.75rem;
        margin-bottom: 5px;
    }
    .ric-header-bar .subtitle {
        color: #8b949e;
        font-size: 0.9rem;
    }
    .ric-card {
        background: #161b22;
        border: 1px solid #30363d;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .ric-card-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #0d1117 100%);
        border-bottom: 1px solid #30363d;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ric-card-header h5 {
        color: #58a6ff;
        margin: 0;
        font-size: 0.95rem;
    }
    .ric-card-body {
        padding: 15px;
    }
    .atom-btn-secondary {
        background: #21262d;
        border: 1px solid #30363d;
        color: #e6edf3;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
    }
    .atom-btn-secondary:hover, .atom-btn-secondary.active {
        background: #30363d;
        border-color: #58a6ff;
        color: #58a6ff;
    }
    .form-control-dark {
        background: #0d1117;
        border: 1px solid #30363d;
        color: #e6edf3;
    }
    .form-control-dark:focus {
        background: #0d1117;
        border-color: #58a6ff;
        color: #e6edf3;
        box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.3);
    }
    .input-group-text {
        background: #21262d;
        border: 1px solid #30363d;
        color: #8b949e;
    }
    .badge-dark {
        background: #30363d;
        color: #e6edf3;
    }
    .ric-legend {
        background: rgba(0,0,0,0.7);
        padding: 10px;
        border-radius: 6px;
    }
    .ric-legend-item {
        color: #e6edf3;
        font-size: 0.75rem;
        margin: 4px 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ric-legend-color {
        width: 12px;
        height: 12px;
        border-radius: 3px;
        display: inline-block;
    }
    .ric-graph-main {
        background: #0d1117 !important;
        border-radius: 8px;
    }
    .modal-content-dark {
        background: #161b22;
        border: 1px solid #30363d;
    }
    .modal-header-dark {
        background: #21262d;
        border-bottom: 1px solid #30363d;
    }
    .btn-success-dark {
        background: #238636;
        border: 1px solid #2ea043;
        color: #fff;
    }
    .btn-success-dark:hover {
        background: #2ea043;
    }
    .dropdown-menu-dark {
        background: #161b22;
        border: 1px solid #30363d;
    }
    .dropdown-menu-dark .dropdown-item {
        color: #e6edf3;
    }
    .dropdown-menu-dark .dropdown-item:hover {
        background: #21262d;
        color: #58a6ff;
    }
</style>
@endpush

@section('content')
<div class="ric-page-container">
    {{-- Header Bar --}}
    <div class="ric-header-bar d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-project-diagram me-2"></i>Graph Explorer</h1>
            <div class="subtitle">Records in Contexts &mdash; Interactive Visualization</div>
        </div>
        <div class="d-flex gap-2">
            <a href="/" class="atom-btn-secondary">
                <i class="fas fa-home me-1"></i> Home
            </a>
            <a href="{{ url('/admin/ric/explorer') }}" class="atom-btn-secondary">
                <i class="fas fa-external-link-alt me-1"></i> Full Version
            </a>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="ric-card">
        <div class="ric-card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="ric-autocomplete-input" class="form-control form-control-dark"
                               placeholder="Search records by title, identifier, or slug..." autocomplete="off" />
                        <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" id="ric-type-filter">
                            <li><a class="dropdown-item active" href="#" data-type="">All Types</a></li>
                            <li><a class="dropdown-item" href="#" data-type="Record">Record</a></li>
                            <li><a class="dropdown-item" href="#" data-type="RecordSet">RecordSet</a></li>
                            <li><a class="dropdown-item" href="#" data-type="Person">Person</a></li>
                            <li><a class="dropdown-item" href="#" data-type="CorporateBody">Corporate Body</a></li>
                            <li><a class="dropdown-item" href="#" data-type="Place">Place</a></li>
                        </ul>
                    </div>
                    <div id="ric-autocomplete-dropdown" class="list-group position-absolute shadow-sm" style="display:none; z-index:1050; max-height:300px; overflow-y:auto; width:calc(100% - 2rem);"></div>
                </div>
                <div class="col-md-4 text-end mt-2 mt-md-0">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="atom-btn-secondary ric-mode-btn active" data-mode="overview">
                            <i class="fas fa-globe me-1"></i> Overview
                        </button>
                        <button type="button" class="atom-btn-secondary ric-mode-btn" data-mode="agent-network">
                            <i class="fas fa-users me-1"></i> Agent Network
                        </button>
                        <div class="vr bg-secondary mx-1"></div>
                        <button type="button" class="atom-btn-secondary ric-view-btn active" data-view="2d">
                            <i class="fas fa-th me-1"></i> 2D
                        </button>
                        <button type="button" class="atom-btn-secondary ric-view-btn" data-view="3d">
                            <i class="fas fa-cube me-1"></i> 3D
                        </button>
                        <button type="button" class="atom-btn-secondary" id="ric-fullscreen-btn" title="Fullscreen">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button type="button" class="atom-btn-secondary" id="ric-create-btn" onclick="openCreateForm()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Graph Container --}}
    <div class="ric-card">
        <div class="ric-card-header">
            <h5><i class="fas fa-project-diagram me-2"></i>Graph View</h5>
            <span id="ric-node-count" class="badge badge-dark">0 nodes</span>
        </div>
        <div class="card-body p-0">
            <div class="ric-graph-main" style="position:relative; height:500px;">
                <div id="ric-explorer-placeholder" style="display:flex; align-items:center; justify-content:center; height:100%; color:#8b949e;">
                    <div class="text-center">
                        <i class="fas fa-project-diagram fa-4x mb-3 opacity-25"></i>
                        <p class="mb-1">Search for a record above, or click "Overview" to visualize the graph</p>
                        <small class="text-muted">Use the search bar to find specific records, or load an overview of all entities</small>
                    </div>
                </div>
                <div id="ric-explorer-loading" style="display:none; align-items:center; justify-content:center; height:100%;">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-2"></div>
                        <p>Loading graph data...</p>
                    </div>
                </div>
                <div id="ric-explorer-graph-2d" style="position:absolute; top:0; left:0; width:100%; height:100%; display:none;"></div>
                <div id="ric-explorer-graph-3d" style="position:absolute; top:0; left:0; width:100%; height:100%; display:none;"></div>

                {{-- Legend --}}
                <div class="ric-legend position-absolute bottom-0 start-0 m-2" style="display:none;" id="ric-explorer-legend">
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#4ecdc4;"></span> RecordSet</div>
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#45b7d1;"></span> Record</div>
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#dc3545;"></span> Person</div>
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#ffc107;"></span> CorporateBody</div>
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#6f42c1;"></span> Activity/Event</div>
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#fd7e14;"></span> Place</div>
                    <div class="ric-legend-item"><span class="ric-legend-color" style="background:#20c997;"></span> Concept</div>
                </div>

                {{-- Controls --}}
                <div class="position-absolute top-0 end-0 m-2 d-flex flex-column gap-1">
                    <button type="button" class="btn btn-sm btn-dark" onclick="zoomIn()" title="Zoom In">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-dark" onclick="zoomOut()" title="Zoom Out">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-dark" onclick="resetZoom()" title="Reset View">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-dark" onclick="toggleLabels()" title="Toggle Labels">
                        <i class="fas fa-tag"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Node Info Panel --}}
    <div id="ric-node-info" class="ric-card" style="display:none;">
        <div class="ric-card-header">
            <h5><i class="fas fa-info-circle me-2"></i>Node Details</h5>
            <button type="button" class="btn btn-sm btn-dark" onclick="document.getElementById('ric-node-info').style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ric-card-body" id="ric-node-info-body"></div>
    </div>

    {{-- Stats Panel --}}
    <div class="ric-card">
        <div class="ric-card-header">
            <h5><i class="fas fa-chart-pie me-2"></i>Statistics</h5>
        </div>
        <div class="ric-card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <div class="text-center p-3 rounded" style="background:#21262d;">
                        <div class="h4 mb-1" id="stat-records">0</div>
                        <small class="text-muted">Records</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="text-center p-3 rounded" style="background:#21262d;">
                        <div class="h4 mb-1" id="stat-agents">0</div>
                        <small class="text-muted">Agents</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="text-center p-3 rounded" style="background:#21262d;">
                        <div class="h4 mb-1" id="stat-places">0</div>
                        <small class="text-muted">Places</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="text-center p-3 rounded" style="background:#21262d;">
                        <div class="h4 mb-1" id="stat-relations">0</div>
                        <small class="text-muted">Relations</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Entity Modal --}}
<div class="modal fade" id="ricCreateEntityModal" tabindex="-1" aria-labelledby="ricCreateEntityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="ricCreateEntityLabel"><i class="fas fa-plus me-2"></i>Create New Entity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Entity Type</label>
                    <select id="ric-create-type" class="form-select form-control-dark">
                        <option value="RecordSet">RecordSet (Fonds/Collection)</option>
                        <option value="Record" selected>Record (Item/File)</option>
                        <option value="RecordPart">RecordPart</option>
                        <option value="Person">Person</option>
                        <option value="CorporateBody">CorporateBody</option>
                        <option value="Family">Family</option>
                        <option value="Place">Place</option>
                        <option value="Activity">Activity</option>
                        <option value="Event">Event</option>
                        <option value="Concept">Concept/Term</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Name / Title <span class="text-danger">*</span></label>
                    <input type="text" id="ric-create-name" class="form-control form-control-dark" placeholder="Enter entity name">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Identifier</label>
                    <input type="text" id="ric-create-identifier" class="form-control form-control-dark" placeholder="Optional identifier">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Description</label>
                    <textarea id="ric-create-description" class="form-control form-control-dark" rows="3" placeholder="Optional description"></textarea>
                </div>
                <div id="ric-create-result" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success-dark" id="ric-create-submit" onclick="submitCreateEntity()">
                    <i class="fas fa-plus me-1"></i>Create Entity
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Fullscreen Modal --}}
<div id="ric-fullscreen-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:#0d1117; z-index:9999;">
    <div style="position:absolute; top:15px; right:15px; z-index:10001;">
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-light ric-fs-view-btn active" data-view="2d">2D</button>
            <button type="button" class="btn btn-light ric-fs-view-btn" data-view="3d">3D</button>
        </div>
        <button type="button" class="btn btn-danger btn-sm ms-2" id="ric-close-fullscreen">
            <i class="fas fa-times"></i> Exit
        </button>
    </div>
    <div id="ric-fullscreen-graph" style="width:100%; height:100%;"></div>
</div>
@endsection

@push('scripts')
<script src="/vendor/openric-ric/js/cytoscape.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://unpkg.com/three-spritetext@1.8.2/dist/three-spritetext.min.js"></script>
<script src="https://unpkg.com/3d-force-graph@1.73.3/dist/3d-force-graph.min.js"></script>
<script src="/vendor/openric-ric/js/ric-explorer.js"></script>
<script>
(function() {
    'use strict';

    var graphData = null;
    var cy2d = null;
    var graph3d = null;
    var fsGraph = null;
    var currentView = '2d';
    var currentMode = 'overview';
    var showLabels = true;
    var autocompleteTimeout = null;

    var autocompleteInput = document.getElementById('ric-autocomplete-input');
    var autocompleteDropdown = document.getElementById('ric-autocomplete-dropdown');

    // Autocomplete
    autocompleteInput.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(autocompleteTimeout);
        if (q.length < 2) {
            autocompleteDropdown.style.display = 'none';
            return;
        }
        autocompleteTimeout = setTimeout(function() {
            fetch('{{ route("ric.public-autocomplete") }}?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(items) {
                    if (!items || items.length === 0) {
                        autocompleteDropdown.style.display = 'none';
                        return;
                    }
                    var html = '';
                    items.forEach(function(item) {
                        html += '<a href="#" class="list-group-item list-group-item-action ric-ac-item" data-id="' + item.id + '">';
                        html += '<div class="d-flex justify-content-between">';
                        html += '<span>' + escapeHtml(item.title) + '</span>';
                        if (item.lod) html += '<span class="badge bg-info">' + escapeHtml(item.lod) + '</span>';
                        html += '</div>';
                        if (item.identifier) html += '<small class="text-muted">' + escapeHtml(item.identifier) + '</small>';
                        html += '</a>';
                    });
                    autocompleteDropdown.innerHTML = html;
                    autocompleteDropdown.style.display = 'block';

                    autocompleteDropdown.querySelectorAll('.ric-ac-item').forEach(function(el) {
                        el.addEventListener('click', function(e) {
                            e.preventDefault();
                            autocompleteDropdown.style.display = 'none';
                            autocompleteInput.value = el.querySelector('span').textContent;
                            loadGraphData(this.dataset.id);
                        });
                    });
                })
                .catch(function() {
                    autocompleteDropdown.style.display = 'none';
                });
        }, 250);
    });

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!autocompleteDropdown.contains(e.target) && e.target !== autocompleteInput) {
            autocompleteDropdown.style.display = 'none';
        }
    });

    // Load graph data
    function loadGraphData(recordId) {
        document.getElementById('ric-explorer-placeholder').style.display = 'none';
        document.getElementById('ric-explorer-loading').style.display = 'flex';
        hideGraphContainers();

        fetch('{{ route("ric.public-data") }}?id=' + encodeURIComponent(recordId) + '&_=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('ric-explorer-loading').style.display = 'none';
                if (data.success && data.graphData && data.graphData.nodes && data.graphData.nodes.length > 0) {
                    graphData = data.graphData;
                    document.getElementById('ric-node-count').textContent = graphData.nodes.length + ' nodes';
                    document.getElementById('ric-explorer-legend').style.display = 'block';
                    updateStats(data.graphData);
                    showGraph();
                } else {
                    document.getElementById('ric-explorer-placeholder').innerHTML = '<div class="text-center"><i class="fas fa-info-circle fa-2x mb-2 text-warning"></i><p>No graph data found for this record</p></div>';
                    document.getElementById('ric-explorer-placeholder').style.display = 'flex';
                }
            })
            .catch(function(err) {
                document.getElementById('ric-explorer-loading').style.display = 'none';
                document.getElementById('ric-explorer-placeholder').innerHTML = '<div class="text-center"><p class="text-danger">Error: ' + escapeHtml(err.message) + '</p></div>';
                document.getElementById('ric-explorer-placeholder').style.display = 'flex';
            });
    }

    function hideGraphContainers() {
        document.getElementById('ric-explorer-graph-2d').style.display = 'none';
        document.getElementById('ric-explorer-graph-3d').style.display = 'none';
    }

    function showGraph() {
        var container2d = document.getElementById('ric-explorer-graph-2d');
        var container3d = document.getElementById('ric-explorer-graph-3d');

        if (currentView === '2d') {
            container3d.style.display = 'none';
            container2d.style.display = 'block';
            setTimeout(function() {
                if (cy2d) { cy2d.destroy(); cy2d = null; }
                container2d.innerHTML = '';
                cy2d = window.RicExplorer.init2DGraph(container2d, graphData, { nodeSize: 25, fontSize: '9px' });
            }, 100);
        } else {
            container2d.style.display = 'none';
            container3d.style.display = 'block';
            setTimeout(function() {
                if (graph3d) {
                    if (graph3d._destructor) graph3d._destructor();
                    graph3d = null;
                }
                container3d.innerHTML = '';
                graph3d = window.RicExplorer.init3DGraph(container3d, graphData);
            }, 100);
        }
    }

    function updateStats(data) {
        var records = 0, agents = 0, places = 0, relations = 0;
        data.nodes.forEach(function(n) {
            var type = n.data.type || '';
            if (type.includes('Record')) records++;
            else if (type.includes('Person') || type.includes('Corporate') || type.includes('Family')) agents++;
            else if (type.includes('Place')) places++;
        });
        relations = data.edges ? data.edges.length : 0;
        document.getElementById('stat-records').textContent = records;
        document.getElementById('stat-agents').textContent = agents;
        document.getElementById('stat-places').textContent = places;
        document.getElementById('stat-relations').textContent = relations;
    }

    function switchView(view) {
        currentView = view;
        document.querySelectorAll('.ric-view-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        if (graphData) showGraph();
    }

    function openFullscreen() {
        if (!graphData) return;
        var modal = document.getElementById('ric-fullscreen-modal');
        var container = document.getElementById('ric-fullscreen-graph');
        modal.style.display = 'block';
        container.innerHTML = '';
        setTimeout(function() {
            if (currentView === '2d') {
                fsGraph = window.RicExplorer.init2DGraph(container, graphData, { nodeSize: 30, fontSize: '10px' });
            } else {
                fsGraph = window.RicExplorer.init3DGraph(container, graphData);
            }
            document.querySelectorAll('.ric-fs-view-btn').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.view === currentView);
            });
        }, 200);
    }

    function closeFullscreen() {
        document.getElementById('ric-fullscreen-modal').style.display = 'none';
        if (fsGraph) {
            if (fsGraph.destroy) fsGraph.destroy();
            if (fsGraph._destructor) fsGraph._destructor();
            fsGraph = null;
        }
        document.getElementById('ric-fullscreen-graph').innerHTML = '';
    }

    function switchFullscreenView(view) {
        currentView = view;
        var container = document.getElementById('ric-fullscreen-graph');
        if (fsGraph) {
            if (fsGraph.destroy) fsGraph.destroy();
            if (fsGraph._destructor) fsGraph._destructor();
            fsGraph = null;
        }
        container.innerHTML = '';
        document.querySelectorAll('.ric-fs-view-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        setTimeout(function() {
            if (view === '2d') {
                fsGraph = window.RicExplorer.init2DGraph(container, graphData, { nodeSize: 30, fontSize: '10px' });
            } else {
                fsGraph = window.RicExplorer.init3DGraph(container, graphData);
            }
        }, 100);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function zoomIn() { if (cy2d) cy2d.zoom(cy2d.zoom() * 1.2); }
    function zoomOut() { if (cy2d) cy2d.zoom(cy2d.zoom() / 1.2); }
    function resetZoom() { if (cy2d) cy2d.fit(); }
    function toggleLabels() {
        showLabels = !showLabels;
        if (cy2d) {
            cy2d.elements().toggleClass('hidden-label', !showLabels);
        }
    }

    // Mode switching (Overview / Agent Network)
    document.querySelectorAll('.ric-mode-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentMode = this.dataset.mode;
            document.querySelectorAll('.ric-mode-btn').forEach(function(b) {
                b.classList.toggle('active', b.dataset.mode === currentMode);
            });
            // Load data based on mode
            if (currentMode === 'overview') {
                loadGraphData('overview');
            } else if (currentMode === 'agent-network') {
                loadGraphData('agent-network');
            }
        });
    });

    // Event listeners
    document.querySelectorAll('.ric-view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { switchView(this.dataset.view); });
    });

    document.getElementById('ric-fullscreen-btn').addEventListener('click', openFullscreen);
    document.getElementById('ric-close-fullscreen').addEventListener('click', closeFullscreen);

    document.querySelectorAll('.ric-fs-view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { switchFullscreenView(this.dataset.view); });
    });

    document.getElementById('ric-load-overview-btn').addEventListener('click', function() {
        loadGraphData('overview');
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeFullscreen();
    });

    autocompleteInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            autocompleteDropdown.style.display = 'none';
            var firstItem = autocompleteDropdown.querySelector('.ric-ac-item');
            if (firstItem) loadGraphData(firstItem.dataset.id);
        }
    });

    // Type filter dropdown
    document.querySelectorAll('#ric-type-filter .dropdown-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#ric-type-filter .dropdown-item').forEach(function(i) {
                i.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
})();

// Create entity functions
window.openCreateForm = function() {
    var modal = new bootstrap.Modal(document.getElementById('ricCreateEntityModal'));
    document.getElementById('ric-create-result').style.display = 'none';
    document.getElementById('ric-create-name').value = '';
    document.getElementById('ric-create-identifier').value = '';
    document.getElementById('ric-create-description').value = '';
    modal.show();
};

window.submitCreateEntity = function() {
    var name = document.getElementById('ric-create-name').value.trim();
    if (!name) { alert('Name is required'); return; }

    var btn = document.getElementById('ric-create-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';

    fetch('{{ route("ric.create-entity") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            type: document.getElementById('ric-create-type').value,
            name: name,
            identifier: document.getElementById('ric-create-identifier').value.trim(),
            description: document.getElementById('ric-create-description').value.trim(),
        }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus me-1"></i>Create Entity';
        var resultDiv = document.getElementById('ric-create-result');
        resultDiv.style.display = '';

        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="fas fa-check me-1"></i>Created: <strong>' + data.name + '</strong> (' + data.type + ')</div>';
            setTimeout(function() {
                bootstrap.Modal.getInstance(document.getElementById('ricCreateEntityModal')).hide();
            }, 1500);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-times me-1"></i>' + (data.error || 'Failed') + '</div>';
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus me-1"></i>Create Entity';
        alert('Error: ' + err.message);
    });
};
</script>
@endpush