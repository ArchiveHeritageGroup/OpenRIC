@extends('theme::layouts.1col')
@section('title', 'Report Select')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <h1><i class="fas fa-file-export me-2"></i>Report Select</h1>
    <p class="text-muted">Select an entity type to generate a report.</p>

    <form method="get" action="{{ route('reports.select') }}">
      <div class="card mb-3">
        <div class="card-header bg-primary text-white">Select Report Type</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Object Type</label>
            <select name="objectType" class="form-select">
              <option value="">-- Select --</option>
              <option value="description">Archival Descriptions</option>
              <option value="agent">Agents / Authority Records</option>
              <option value="repository">Repositories</option>
              <option value="accession">Accessions</option>
              <option value="donor">Donors</option>
              <option value="physical_storage">Physical Storage</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right me-1"></i>Go to Report</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
