<div class="card mb-3">
  <div class="card-header bg-primary text-white"><i class="fas fa-chart-bar me-2"></i>Reports</div>
  <div class="list-group list-group-flush">
    <a href="{{ route('reports.dashboard') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.dashboard') ? ' active' : '' }}"><i class="fas fa-tachometer-alt me-2" style="width:18px"></i>Dashboard</a>
    <a href="{{ route('reports.descriptions') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.descriptions') ? ' active' : '' }}"><i class="fas fa-file-alt me-2" style="width:18px"></i>Descriptions</a>
    <a href="{{ route('reports.agents') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.agents') ? ' active' : '' }}"><i class="fas fa-user me-2" style="width:18px"></i>Agents</a>
    <a href="{{ route('reports.repositories') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.repositories') ? ' active' : '' }}"><i class="fas fa-university me-2" style="width:18px"></i>Repositories</a>
    <a href="{{ route('reports.accessions') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.accessions') ? ' active' : '' }}"><i class="fas fa-inbox me-2" style="width:18px"></i>Accessions</a>
    <a href="{{ route('reports.donors') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.donors') ? ' active' : '' }}"><i class="fas fa-hand-holding-heart me-2" style="width:18px"></i>Donors</a>
    <a href="{{ route('reports.storage') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.storage') ? ' active' : '' }}"><i class="fas fa-box me-2" style="width:18px"></i>Physical Storage</a>
    <a href="{{ route('reports.taxonomy') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.taxonomy') ? ' active' : '' }}"><i class="fas fa-tags me-2" style="width:18px"></i>Taxonomies</a>
    <a href="{{ route('reports.recent') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.recent') ? ' active' : '' }}"><i class="fas fa-clock me-2" style="width:18px"></i>Recent Updates</a>
    <a href="{{ route('reports.spatial') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.spatial') ? ' active' : '' }}"><i class="fas fa-map-marker-alt me-2" style="width:18px"></i>Spatial Analysis</a>
    <a href="{{ route('reports.access') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.access') ? ' active' : '' }}"><i class="fas fa-shield-alt me-2" style="width:18px"></i>Access / Rights</a>
    <a href="{{ route('reports.activity') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.activity') ? ' active' : '' }}"><i class="fas fa-history me-2" style="width:18px"></i>User Activity</a>
    <a href="{{ route('reports.collections') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.collections') ? ' active' : '' }}"><i class="fas fa-layer-group me-2" style="width:18px"></i>Collections</a>
    <a href="{{ route('reports.search') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.search') ? ' active' : '' }}"><i class="fas fa-search me-2" style="width:18px"></i>Search Analytics</a>
    <a href="{{ route('reports.builder.index') }}" class="list-group-item list-group-item-action{{ request()->routeIs('reports.builder.*') ? ' active' : '' }}"><i class="fas fa-wrench me-2" style="width:18px"></i>Report Builder</a>
  </div>
</div>
