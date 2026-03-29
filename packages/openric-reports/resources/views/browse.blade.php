@extends('theme::layouts.1col')
@section('title', 'Browse Reports')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('reports::_menu')

    <div class="card mb-3">
      <div class="card-header bg-primary text-white"><i class="fas fa-filter me-2"></i>Storage Filters</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Strong Rooms:</label>
          <select name="strongroom" id="strongroomSelect" class="form-select form-select-sm">
            <option value="">Select</option>
            @foreach($strongrooms ?? [] as $room)
              <option value="{{ $room }}">{{ $room }}</option>
            @endforeach
            <option value="All">All</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Location:</label>
          <select name="location" id="locationSelect" class="form-select form-select-sm">
            <option value="">Select</option>
            @foreach($locations ?? [] as $loc)
              <option value="{{ $loc }}">{{ $loc }}</option>
            @endforeach
            <option value="All">All</option>
          </select>
        </div>
        <div class="d-grid gap-2">
          <a href="{{ route('reports.browse', ['action' => 'search']) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Search</a>
        </div>
      </div>
    </div>

    <div class="list-group mb-3">
      <a href="{{ route('reports.browse-publish') }}" class="list-group-item list-group-item-action"><i class="fas fa-eye me-2 text-muted"></i>Publish</a>
    </div>
  </div>

  <div class="col-md-9">
    <h1><i class="fas fa-list me-2"></i>Browse Reports</h1>
    <p class="text-muted mb-4">Use the sidebar filters to select a strong room or location, then click Search to filter physical storage records.</p>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header bg-primary text-white"><i class="fas fa-clipboard-check me-2"></i>Audit Reports</div>
          <div class="list-group list-group-flush">
            <a href="{{ route('reports.audit.descriptions') }}" class="list-group-item list-group-item-action"><i class="fas fa-file-alt me-2" style="width:18px"></i>Audit Descriptions</a>
            <a href="{{ route('reports.audit.agents') }}" class="list-group-item list-group-item-action"><i class="fas fa-user me-2" style="width:18px"></i>Audit Agents</a>
            <a href="{{ route('reports.audit.donors') }}" class="list-group-item list-group-item-action"><i class="fas fa-hand-holding-heart me-2" style="width:18px"></i>Audit Donors</a>
            <a href="{{ route('reports.audit.permissions') }}" class="list-group-item list-group-item-action"><i class="fas fa-key me-2" style="width:18px"></i>Audit Permissions</a>
            <a href="{{ route('reports.audit.physical-storage') }}" class="list-group-item list-group-item-action"><i class="fas fa-box me-2" style="width:18px"></i>Audit Physical Storage</a>
            <a href="{{ route('reports.audit.repositories') }}" class="list-group-item list-group-item-action"><i class="fas fa-university me-2" style="width:18px"></i>Audit Repositories</a>
            <a href="{{ route('reports.audit.taxonomies') }}" class="list-group-item list-group-item-action"><i class="fas fa-tags me-2" style="width:18px"></i>Audit Taxonomies</a>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header bg-primary text-white"><i class="fas fa-wrench me-2"></i>Tools</div>
          <div class="list-group list-group-flush">
            <a href="{{ route('reports.select') }}" class="list-group-item list-group-item-action"><i class="fas fa-file-export me-2" style="width:18px"></i>Report Select</a>
            <a href="{{ route('reports.report') }}" class="list-group-item list-group-item-action"><i class="fas fa-chart-bar me-2" style="width:18px"></i>Generic Report</a>
            <a href="{{ route('reports.builder.index') }}" class="list-group-item list-group-item-action"><i class="fas fa-tools me-2" style="width:18px"></i>Report Builder</a>
            <a href="{{ route('reports.access') }}" class="list-group-item list-group-item-action"><i class="fas fa-shield-alt me-2" style="width:18px"></i>Access Report</a>
            <a href="{{ route('reports.spatial') }}" class="list-group-item list-group-item-action"><i class="fas fa-map-marker-alt me-2" style="width:18px"></i>Spatial Analysis</a>
            <a href="{{ route('reports.activity') }}" class="list-group-item list-group-item-action"><i class="fas fa-user-clock me-2" style="width:18px"></i>User Activity</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
