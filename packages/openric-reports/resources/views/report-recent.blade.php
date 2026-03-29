@extends('theme::layouts.1col')
@section('title', 'Recent Updates Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('reports::_menu')
    @include('reports::_filters', [
      'action' => route('reports.recent'),
      'extraFilters' => '
        <div class="mb-3">
          <label class="form-label">Entity type <span class="badge bg-secondary ms-1">Optional</span></label>
          <select name="entityType" class="form-select form-select-sm">
            <option value="">All types</option>
            <option value="RecordDescription"' . (($params['entityType'] ?? '') === 'RecordDescription' ? ' selected' : '') . '>Descriptions</option>
            <option value="Agent"' . (($params['entityType'] ?? '') === 'Agent' ? ' selected' : '') . '>Agents</option>
            <option value="Repository"' . (($params['entityType'] ?? '') === 'Repository' ? ' selected' : '') . '>Repositories</option>
            <option value="Accession"' . (($params['entityType'] ?? '') === 'Accession' ? ' selected' : '') . '>Accessions</option>
            <option value="PhysicalStorage"' . (($params['entityType'] ?? '') === 'PhysicalStorage' ? ' selected' : '') . '>Physical Storage</option>
            <option value="Donor"' . (($params['entityType'] ?? '') === 'Donor' ? ' selected' : '') . '>Donors</option>
          </select>
        </div>',
    ])
  </div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-clock me-2"></i>Recent Updates</h1>
      <div>
        <span class="badge bg-primary fs-6">{{ number_format($total) }} results</span>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success ms-2"><i class="fas fa-file-csv me-1"></i>CSV</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm">
        <thead><tr><th>#</th><th>Type</th><th>Entity ID</th><th>Action</th><th>Date</th></tr></thead>
        <tbody>
          @forelse($results as $row)
            <tr>
              <td>{{ $row->id }}</td>
              <td>{{ str_replace('_', ' ', ucfirst($row->entity_type ?? '')) }}</td>
              <td>{{ $row->entity_id ?? '' }}</td>
              <td>
                @php $badge = match($row->action ?? '') { 'create' => 'bg-success', 'update' => 'bg-primary', 'delete' => 'bg-danger', default => 'bg-secondary' }; @endphp
                <span class="badge {{ $badge }}">{{ $row->action ?? '' }}</span>
              </td>
              <td>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') : '' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted text-center">No results</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('reports::_pagination')
  </div>
</div>
@endsection
