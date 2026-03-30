@extends('theme::layouts.1col')

@section('title', 'Export Rights Data')

@section('content')
  <h1 class="mb-3">Export Rights Data</h1>

  <div class="card mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Rights Coverage</h5></div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col"><h3>{{ number_format($stats['total_statements'] ?? 0) }}</h3><small class="text-muted">Total Statements</small></div>
        <div class="col"><h3>{{ number_format($stats['active_embargoes'] ?? 0) }}</h3><small class="text-muted">Active Embargoes</small></div>
        <div class="col"><h3>{{ number_format($stats['total_tk_labels'] ?? 0) }}</h3><small class="text-muted">TK Labels</small></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Export Options</h5></div>
    <div class="card-body">
      <p class="text-muted">Export functionality will generate CSV reports of rights data.</p>
      <a href="{{ route('rights.admin.report') }}" class="btn btn-outline-primary">View Rights Report</a>
    </div>
  </div>
@endsection
