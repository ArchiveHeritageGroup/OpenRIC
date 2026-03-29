@extends('theme::layouts.1col')
@section('title', 'Create Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <h1><i class="fas fa-plus me-2"></i>Create New Report</h1>

    <form method="post" action="{{ route('reports.builder.store') }}">
      @csrf
      <div class="card mb-3">
        <div class="card-header bg-primary text-white">Report Details</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Report Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Data Source <span class="text-danger">*</span></label>
              <select name="data_source" class="form-select" required>
                <option value="record_descriptions">Descriptions</option>
                <option value="agents">Agents</option>
                <option value="accessions">Accessions</option>
                <option value="repositories">Repositories</option>
                <option value="physical_storage_locations">Physical Storage</option>
                <option value="donors">Donors</option>
                <option value="audit_log">Audit Log</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Category</label>
              <select name="category" class="form-select">
                <option value="Archives">Archives</option>
                <option value="Collections">Collections</option>
                <option value="Heritage">Heritage</option>
                <option value="Compliance">Compliance</option>
                <option value="General">General</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Visibility</label>
              <select name="visibility" class="form-select">
                <option value="private">Private</option>
                <option value="shared">Shared</option>
                <option value="public">Public</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Report</button>
        <a href="{{ route('reports.builder.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
