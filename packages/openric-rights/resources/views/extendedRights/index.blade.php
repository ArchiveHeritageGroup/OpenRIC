@extends('theme::layouts.1col')

@section('title', 'Extended Rights Management')

@section('content')
  <h1 class="mb-3">Extended Rights Management</h1>

  <div class="row">
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Rights Statements</h5></div>
        <div class="card-body">
          <p class="text-muted small">Standardized rights statements for cultural heritage institutions.</p>
          <h3>{{ number_format($stats['total_statements'] ?? 0) }}</h3>
          <small class="text-muted">Total statements</small>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-warning text-dark"><h5 class="mb-0">Embargoes</h5></div>
        <div class="card-body">
          <p class="text-muted small">Access restrictions on archival entities.</p>
          <h3>{{ number_format($stats['active_embargoes'] ?? 0) }}</h3>
          <small class="text-muted">Active embargoes</small>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-success text-white"><h5 class="mb-0">TK Labels</h5></div>
        <div class="card-body">
          <p class="text-muted small">Labels for Indigenous cultural heritage.</p>
          <h3>{{ number_format($stats['total_tk_labels'] ?? 0) }}</h3>
          <small class="text-muted">Total TK labels</small>
        </div>
      </div>
    </div>
  </div>

  @auth
  <div class="card mt-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Administration</h5></div>
    <div class="card-body d-flex gap-2 flex-wrap">
      <a href="{{ route('rights.extended.batch') }}" class="btn btn-outline-primary">Batch Assign Rights</a>
      <a href="{{ route('rights.extended.embargoes') }}" class="btn btn-outline-primary">Manage Embargoes</a>
      <a href="{{ route('rights.extended.export') }}" class="btn btn-outline-primary">Export Rights</a>
    </div>
  </div>
  @endauth
@endsection
