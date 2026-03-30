@extends('theme::layouts.1col')

@section('title', 'Rights Administration')

@section('content')
  <h1 class="mb-3">Rights Administration</h1>

  <div class="row mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body text-center"><h2>{{ number_format($stats['total_statements'] ?? 0) }}</h2><small class="text-muted">Total Rights</small></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body text-center"><h2>{{ number_format($stats['active_embargoes'] ?? 0) }}</h2><small class="text-muted">Active Embargoes</small></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body text-center"><h2>{{ number_format($stats['orphan_works'] ?? 0) }}</h2><small class="text-muted">Orphan Works</small></div></div></div>
  </div>

  @include('rights::rightsAdmin._sidebar')
@endsection
