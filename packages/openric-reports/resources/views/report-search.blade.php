@extends('theme::layouts.1col')
@section('title', 'Search Analytics')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-search me-2"></i>Search Analytics</h1>
      <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i>CSV</a>
    </div>

    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card text-center bg-primary text-white">
          <div class="card-body"><h2 class="mb-0">{{ number_format($data['total_searches'] ?? 0) }}</h2><p class="mb-0">Total Searches</p></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card text-center bg-info text-white">
          <div class="card-body"><h2 class="mb-0">{{ number_format($data['recent_searches'] ?? 0) }}</h2><p class="mb-0">Searches (7 days)</p></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white"><i class="fas fa-chart-bar me-2"></i>Top Search Terms (30 days)</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-striped mb-0">
          <thead><tr><th>Search Term</th><th class="text-end">Count</th></tr></thead>
          <tbody>
            @forelse($data['top_search_terms'] ?? [] as $term)
            <tr>
              <td>{{ $term->search_term ?? '' }}</td>
              <td class="text-end"><span class="badge bg-primary">{{ number_format($term->count ?? 0) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="2" class="text-muted text-center">No search data available</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
