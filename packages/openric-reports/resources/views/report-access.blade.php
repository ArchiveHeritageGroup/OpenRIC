@extends('theme::layouts.1col')
@section('title', 'Access / Rights Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-shield-alt me-2"></i>Access / Rights Report</h1>
      <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</a>
    </div>

    {{-- Embargo Summary --}}
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card text-center bg-danger text-white">
          <div class="card-body"><h2 class="mb-0">{{ $embargoes['active'] ?? 0 }}</h2><p class="mb-0">Active Embargoes</p></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center bg-success text-white">
          <div class="card-body"><h2 class="mb-0">{{ $embargoes['lifted'] ?? 0 }}</h2><p class="mb-0">Lifted Embargoes</p></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center bg-secondary text-white">
          <div class="card-body"><h2 class="mb-0">{{ $embargoes['expired'] ?? 0 }}</h2><p class="mb-0">Expired Embargoes</p></div>
        </div>
      </div>
    </div>

    {{-- Rights by Basis --}}
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-balance-scale me-2"></i>Rights Statements by Basis</div>
      <div class="card-body">
        @if(!empty($rights_by_basis))
          <table class="table table-sm table-bordered">
            <thead><tr><th>Rights Basis</th><th class="text-end">Count</th></tr></thead>
            <tbody>
              @foreach($rights_by_basis as $basis => $count)
              <tr><td>{{ ucfirst($basis) }}</td><td class="text-end"><span class="badge bg-primary">{{ number_format($count) }}</span></td></tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr><th>Total Rights Statements</th><th class="text-end">{{ number_format($total_rights ?? 0) }}</th></tr>
              <tr><th>Total TK Labels</th><th class="text-end">{{ number_format($total_tk_labels ?? 0) }}</th></tr>
            </tfoot>
          </table>
        @else
          <p class="text-muted text-center">No rights statements found.</p>
        @endif
      </div>
    </div>

    {{-- Restricted Records --}}
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-lock me-2"></i>Records with Restrictions</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-striped mb-0">
          <thead><tr><th>#</th><th>Identifier</th><th>Title</th><th>Status</th><th>Rights Basis</th><th>Restriction</th><th>Embargo Expiry</th></tr></thead>
          <tbody>
            @forelse($restricted_records ?? collect() as $row)
            <tr>
              <td>{{ $row->id }}</td>
              <td><code>{{ $row->identifier ?? '' }}</code></td>
              <td>{{ Str::limit($row->title ?? '', 50) }}</td>
              <td>
                @if(($row->publication_status ?? '') === 'published')
                  <span class="badge bg-success">Published</span>
                @else
                  <span class="badge bg-warning text-dark">Draft</span>
                @endif
              </td>
              <td>{{ $row->rights_basis ?? '' }}</td>
              <td>{{ $row->restriction_type ?? '' }}</td>
              <td>{{ $row->embargo_expiry ?? '' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-muted text-center">No restricted records found</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
