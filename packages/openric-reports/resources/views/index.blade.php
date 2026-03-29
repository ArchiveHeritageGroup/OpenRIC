@extends('theme::layouts.1col')
@section('title', 'Reports Index')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <h1><i class="fas fa-chart-bar me-2"></i>Reports</h1>
    <p class="text-muted">Select a report from the sidebar menu or use the quick links below.</p>

    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">Entity Reports</div>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><a href="{{ route('reports.descriptions') }}"><i class="fas fa-file-alt me-2 text-muted"></i>Descriptions</a></li>
            <li class="list-group-item"><a href="{{ route('reports.agents') }}"><i class="fas fa-users me-2 text-muted"></i>Agents</a></li>
            <li class="list-group-item"><a href="{{ route('reports.repositories') }}"><i class="fas fa-university me-2 text-muted"></i>Repositories</a></li>
            <li class="list-group-item"><a href="{{ route('reports.accessions') }}"><i class="fas fa-inbox me-2 text-muted"></i>Accessions</a></li>
            <li class="list-group-item"><a href="{{ route('reports.donors') }}"><i class="fas fa-hand-holding-heart me-2 text-muted"></i>Donors</a></li>
            <li class="list-group-item"><a href="{{ route('reports.storage') }}"><i class="fas fa-box me-2 text-muted"></i>Physical Storage</a></li>
            <li class="list-group-item"><a href="{{ route('reports.taxonomy') }}"><i class="fas fa-tags me-2 text-muted"></i>Taxonomies</a></li>
          </ul>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header bg-info text-white">Analytics & Tools</div>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><a href="{{ route('reports.recent') }}"><i class="fas fa-clock me-2 text-muted"></i>Recent Updates</a></li>
            <li class="list-group-item"><a href="{{ route('reports.activity') }}"><i class="fas fa-history me-2 text-muted"></i>User Activity</a></li>
            <li class="list-group-item"><a href="{{ route('reports.access') }}"><i class="fas fa-shield-alt me-2 text-muted"></i>Access / Rights</a></li>
            <li class="list-group-item"><a href="{{ route('reports.collections') }}"><i class="fas fa-layer-group me-2 text-muted"></i>Collections</a></li>
            <li class="list-group-item"><a href="{{ route('reports.search') }}"><i class="fas fa-search me-2 text-muted"></i>Search Analytics</a></li>
            <li class="list-group-item"><a href="{{ route('reports.spatial') }}"><i class="fas fa-map-marker-alt me-2 text-muted"></i>Spatial Analysis</a></li>
            <li class="list-group-item"><a href="{{ route('reports.builder.index') }}"><i class="fas fa-wrench me-2 text-muted"></i>Report Builder</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
