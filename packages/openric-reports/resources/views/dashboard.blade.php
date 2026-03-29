@extends('theme::layouts.1col')
@section('title', 'Reports Dashboard')
@section('body-class', 'admin reports')

@php
  $packages = $enabledPackages ?? [];
  $has = fn($name) => isset($packages[$name]);

  $hasGallery = $has('openric-gallery');
  $hasResearch = $has('openric-research');
  $hasRights = $has('openric-rights');
  $hasHeritage = $has('openric-heritage');
  $hasExhibition = $has('openric-exhibition');
  $hasDataMigration = $has('openric-data-migration');
@endphp

@section('content')
<div class="reports-dashboard">
  <div class="row">
    <div class="col-md-3">@include('reports::_menu')</div>
    <div class="col-md-9">

      <h1><i class="fas fa-tachometer-alt"></i> Reports Dashboard</h1>

      {{-- Stats Row --}}
      <div class="row mb-4">
        <div class="col-md-3"><div class="card text-center bg-primary text-white"><div class="card-body"><h2 class="mb-0">{{ number_format($stats['descriptions'] ?? 0) }}</h2><p class="mb-0">Descriptions</p></div></div></div>
        <div class="col-md-3"><div class="card text-center bg-success text-white"><div class="card-body"><h2 class="mb-0">{{ number_format($stats['agents'] ?? 0) }}</h2><p class="mb-0">Agents</p></div></div></div>
        <div class="col-md-3"><div class="card text-center bg-info text-white"><div class="card-body"><h2 class="mb-0">{{ number_format($stats['digital_objects'] ?? 0) }}</h2><p class="mb-0">Digital Objects</p></div></div></div>
        <div class="col-md-3"><div class="card text-center bg-warning text-dark"><div class="card-body"><h2 class="mb-0">{{ number_format($stats['recent_updates'] ?? 0) }}</h2><p class="mb-0">Updated (7 days)</p></div></div></div>
      </div>

      {{-- Extended Stats --}}
      <div class="row mb-4">
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="mb-0">{{ number_format($stats['repositories'] ?? 0) }}</h3><p class="mb-0 text-muted">Repositories</p></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="mb-0">{{ number_format($stats['accessions'] ?? 0) }}</h3><p class="mb-0 text-muted">Accessions</p></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="mb-0">{{ number_format($stats['donors'] ?? 0) }}</h3><p class="mb-0 text-muted">Donors</p></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h3 class="mb-0">{{ number_format($stats['physical_storage'] ?? 0) }}</h3><p class="mb-0 text-muted">Storage Locations</p></div></div></div>
      </div>

      {{-- Publication Status & RiC-O Stats --}}
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Publication Status</h5></div>
            <div class="card-body">
              <table class="table table-sm mb-0">
                <tr><td>Published</td><td class="text-end"><span class="badge bg-success">{{ number_format($stats['published'] ?? 0) }}</span></td></tr>
                <tr><td>Draft</td><td class="text-end"><span class="badge bg-warning text-dark">{{ number_format($stats['draft'] ?? 0) }}</span></td></tr>
                <tr><td>Active Embargoes</td><td class="text-end"><span class="badge bg-danger">{{ number_format($stats['active_embargoes'] ?? 0) }}</span></td></tr>
                <tr><td>Rights Statements</td><td class="text-end"><span class="badge bg-info">{{ number_format($stats['total_rights'] ?? 0) }}</span></td></tr>
              </table>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-database me-2"></i>System Statistics</h5></div>
            <div class="card-body">
              <table class="table table-sm mb-0">
                <tr><td>Total RiC-O Entities</td><td class="text-end"><span class="badge bg-primary">{{ number_format($stats['total_entities'] ?? 0) }}</span></td></tr>
                <tr><td>Total Triples</td><td class="text-end"><span class="badge bg-primary">{{ number_format($stats['total_triples'] ?? 0) }}</span></td></tr>
                <tr><td>Users</td><td class="text-end"><span class="badge bg-secondary">{{ number_format($stats['total_users'] ?? 0) }}</span></td></tr>
                <tr><td>Recent Logins (7d)</td><td class="text-end"><span class="badge bg-secondary">{{ number_format($stats['recent_logins'] ?? 0) }}</span></td></tr>
                <tr><td>Database Size</td><td class="text-end"><span class="badge bg-dark">{{ $stats['database_size'] ?? 'N/A' }}</span></td></tr>
              </table>
            </div>
          </div>
        </div>
      </div>

      {{-- RiC-O Type Breakdown --}}
      @php
        $ricoTypes = array_filter($stats, fn($v, $k) => !in_array($k, ['total_entities','total_triples','total_users','recent_logins','descriptions','agents','repositories','accessions','digital_objects','donors','physical_storage','published','draft','recent_updates','active_embargoes','total_rights','database_size']), ARRAY_FILTER_USE_BOTH);
      @endphp
      @if(count($ricoTypes) > 0)
      <div class="card mb-4">
        <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i>RiC-O Entity Types</h5></div>
        <div class="card-body">
          <div class="row">
            @foreach($ricoTypes as $type => $count)
            <div class="col-md-3 mb-2">
              <div class="d-flex justify-content-between align-items-center border rounded p-2">
                <span>{{ $type }}</span>
                <span class="badge bg-primary">{{ number_format($count) }}</span>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      {{-- Quick Links --}}
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Reports</h5></div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><a href="{{ route('reports.descriptions') }}"><i class="fas fa-archive me-2 text-muted"></i>Archival Descriptions</a></li>
              <li class="list-group-item"><a href="{{ route('reports.agents') }}"><i class="fas fa-users me-2 text-muted"></i>Agents</a></li>
              <li class="list-group-item"><a href="{{ route('reports.repositories') }}"><i class="fas fa-building me-2 text-muted"></i>Repositories</a></li>
              <li class="list-group-item"><a href="{{ route('reports.accessions') }}"><i class="fas fa-inbox me-2 text-muted"></i>Accessions</a></li>
              <li class="list-group-item"><a href="{{ route('reports.donors') }}"><i class="fas fa-handshake me-2 text-muted"></i>Donors</a></li>
              <li class="list-group-item"><a href="{{ route('reports.storage') }}"><i class="fas fa-boxes me-2 text-muted"></i>Physical Storage</a></li>
              <li class="list-group-item"><a href="{{ route('reports.spatial') }}"><i class="fas fa-map-marker-alt me-2 text-muted"></i>Spatial Analysis</a></li>
              @if($hasGallery)<li class="list-group-item"><a href="{{ url('/gallery/reports') }}"><i class="fas fa-palette me-2 text-muted"></i>Gallery Reports</a></li>@endif
              @if($hasHeritage)<li class="list-group-item"><a href="{{ url('/heritage/reports') }}"><i class="fas fa-landmark me-2 text-muted"></i>Heritage Reports</a></li>@endif
            </ul>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Audit Reports</h5></div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><a href="{{ route('reports.audit.descriptions') }}"><i class="fas fa-file-alt me-2 text-muted"></i>Audit Descriptions</a></li>
              <li class="list-group-item"><a href="{{ route('reports.audit.agents') }}"><i class="fas fa-user me-2 text-muted"></i>Audit Agents</a></li>
              <li class="list-group-item"><a href="{{ route('reports.audit.repositories') }}"><i class="fas fa-university me-2 text-muted"></i>Audit Repositories</a></li>
              <li class="list-group-item"><a href="{{ route('reports.audit.donors') }}"><i class="fas fa-hand-holding-heart me-2 text-muted"></i>Audit Donors</a></li>
              <li class="list-group-item"><a href="{{ route('reports.audit.physical-storage') }}"><i class="fas fa-box me-2 text-muted"></i>Audit Physical Storage</a></li>
              <li class="list-group-item"><a href="{{ route('reports.audit.permissions') }}"><i class="fas fa-key me-2 text-muted"></i>Audit Permissions</a></li>
              <li class="list-group-item"><a href="{{ route('reports.audit.taxonomies') }}"><i class="fas fa-tags me-2 text-muted"></i>Audit Taxonomies</a></li>
            </ul>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-download me-2"></i>Tools & Export</h5></div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><a href="{{ route('reports.builder.index') }}"><i class="fas fa-wrench me-2 text-muted"></i>Report Builder</a></li>
              <li class="list-group-item"><a href="{{ route('reports.browse') }}"><i class="fas fa-list me-2 text-muted"></i>Browse Reports</a></li>
              <li class="list-group-item"><a href="{{ route('reports.select') }}"><i class="fas fa-file-export me-2 text-muted"></i>Report Select</a></li>
              <li class="list-group-item"><a href="{{ route('reports.collections') }}"><i class="fas fa-layer-group me-2 text-muted"></i>Collections</a></li>
              <li class="list-group-item"><a href="{{ route('reports.search') }}"><i class="fas fa-search me-2 text-muted"></i>Search Analytics</a></li>
              <li class="list-group-item"><a href="{{ route('reports.activity') }}"><i class="fas fa-history me-2 text-muted"></i>User Activity</a></li>
              <li class="list-group-item"><a href="{{ route('reports.access') }}"><i class="fas fa-shield-alt me-2 text-muted"></i>Access / Rights</a></li>
            </ul>
          </div>
        </div>
      </div>

      {{-- Creation Stats Chart --}}
      @if(!empty($creationStats['data']))
      <div class="card mb-4">
        <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Creation Trend ({{ ucfirst($creationStats['period'] ?? 'month') }})</h5></div>
        <div class="card-body">
          <table class="table table-sm table-striped">
            <thead><tr><th>Period</th><th class="text-end">Count</th></tr></thead>
            <tbody>
              @foreach($creationStats['data'] as $period => $count)
              <tr><td>{{ $period }}</td><td class="text-end"><span class="badge bg-primary">{{ number_format($count) }}</span></td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif

    </div>
  </div>
</div>
@endsection
