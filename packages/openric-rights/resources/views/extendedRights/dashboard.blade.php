@extends('theme::layouts.1col')

@section('title', 'Extended Rights Dashboard')

@section('content')
  <h1 class="mb-3">Extended Rights Dashboard</h1>

  <div class="row mb-4">
    <div class="col-md-3"><div class="card text-white bg-primary"><div class="card-body"><h5 class="card-title">Total Statements</h5><h2>{{ number_format($stats['total_statements'] ?? 0) }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-white bg-warning"><div class="card-body"><h5 class="card-title">Active Embargoes</h5><h2>{{ number_format($stats['active_embargoes'] ?? 0) }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-white bg-danger"><div class="card-body"><h5 class="card-title">Lifted Embargoes</h5><h2>{{ number_format($stats['lifted_embargoes'] ?? 0) }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-white bg-info"><div class="card-body"><h5 class="card-title">TK Labels</h5><h2>{{ number_format($stats['total_tk_labels'] ?? 0) }}</h2></div></div></div>
  </div>

  @if(!empty($stats['statements_by_basis']))
  <div class="card mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">By Rights Basis</h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Basis</th><th class="text-end">Count</th></tr></thead>
        <tbody>
          @foreach($stats['statements_by_basis'] as $basis => $count)
            <tr><td>{{ ucfirst($basis) }}</td><td class="text-end">{{ number_format($count) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Quick Actions</h5></div>
    <div class="card-body d-flex gap-2 flex-wrap">
      <a href="{{ route('rights.extended.batch') }}" class="btn btn-outline-primary">Batch Assign Rights</a>
      <a href="{{ route('rights.extended.embargoes') }}" class="btn btn-outline-primary">Manage Embargoes</a>
      <a href="{{ route('rights.extended.export') }}" class="btn btn-outline-primary">Export Rights</a>
      <a href="{{ route('rights.admin.index') }}" class="btn btn-outline-secondary">Rights Admin</a>
    </div>
  </div>
@endsection
