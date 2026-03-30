{{-- Reports navigation menu --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('reports.dashboard') }}" class="btn btn-sm {{ request()->routeIs('reports.dashboard') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
    <a href="{{ route('reports.descriptions') }}" class="btn btn-sm {{ request()->routeIs('reports.descriptions') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-file-alt me-1"></i>Descriptions</a>
    <a href="{{ route('reports.agents') }}" class="btn btn-sm {{ request()->routeIs('reports.agents') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-users me-1"></i>Agents</a>
    <a href="{{ route('reports.repositories') }}" class="btn btn-sm {{ request()->routeIs('reports.repositories') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-building me-1"></i>Repositories</a>
    <a href="{{ route('reports.accessions') }}" class="btn btn-sm {{ request()->routeIs('reports.accessions') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-box me-1"></i>Accessions</a>
    <a href="{{ route('reports.donors') }}" class="btn btn-sm {{ request()->routeIs('reports.donors') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-hand-holding me-1"></i>Donors</a>
    <a href="{{ route('reports.storage') }}" class="btn btn-sm {{ request()->routeIs('reports.storage') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-warehouse me-1"></i>Storage</a>
    <a href="{{ route('reports.taxonomy') }}" class="btn btn-sm {{ request()->routeIs('reports.taxonomy') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-tags me-1"></i>Taxonomy</a>
    <a href="{{ route('reports.activity') }}" class="btn btn-sm {{ request()->routeIs('reports.activity') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-history me-1"></i>Activity</a>
    <a href="{{ route('reports.recent') }}" class="btn btn-sm {{ request()->routeIs('reports.recent') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-clock me-1"></i>Recent</a>
    <a href="{{ route('reports.spatial') }}" class="btn btn-sm {{ request()->routeIs('reports.spatial') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-map me-1"></i>Spatial</a>
    <a href="{{ route('reports.builder.index') }}" class="btn btn-sm {{ request()->routeIs('reports.builder.*') ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="fas fa-wrench me-1"></i>Builder</a>
</div>
