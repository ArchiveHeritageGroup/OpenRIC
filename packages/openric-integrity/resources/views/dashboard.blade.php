@extends('theme::layouts.1col')
@section('title', 'Integrity Check')
@section('content')
<div class="d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-shield-alt me-3"></i>
    <div><h1 class="mb-0">Integrity Check</h1><span class="small text-muted">RDF triplestore and PostgreSQL integrity monitoring</span></div>
</div>

@include('theme::partials.alerts')

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-3 fw-bold">{{ $stats['total_runs'] ?? 0 }}</div><div class="small text-muted">Total Runs</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card text-center border-success"><div class="card-body py-2"><div class="fs-3 fw-bold text-success">{{ $stats['pass_rate'] ?? 0 }}%</div><div class="small text-muted">Pass Rate</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card text-center {{ ($stats['open_issues'] ?? 0) > 0 ? 'border-danger' : 'border-secondary' }}"><div class="card-body py-2"><div class="fs-3 fw-bold {{ ($stats['open_issues'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $stats['open_issues'] ?? 0 }}</div><div class="small text-muted">Open Issues</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card text-center border-info"><div class="card-body py-2"><div class="fs-3 fw-bold text-info">{{ $stats['last_run'] ? \Carbon\Carbon::parse($stats['last_run'])->diffForHumans() : 'Never' }}</div><div class="small text-muted">Last Run</div></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <form action="{{ route('integrity.run') }}" method="POST" class="d-inline">@csrf
        <button type="submit" class="btn btn-primary"><i class="fas fa-play me-1"></i>Run Integrity Check</button>
    </form>
    <a href="{{ route('integrity.alerts') }}" class="btn btn-outline-secondary"><i class="fas fa-bell me-1"></i>Alerts</a>
    <a href="{{ route('integrity.dead-letter') }}" class="btn btn-outline-secondary"><i class="fas fa-exclamation-circle me-1"></i>Dead Letters</a>
    <a href="{{ route('integrity.ledger') }}" class="btn btn-outline-secondary"><i class="fas fa-clipboard-list me-1"></i>Ledger</a>
    <a href="{{ route('integrity.policies') }}" class="btn btn-outline-secondary"><i class="fas fa-cogs me-1"></i>Policies</a>
    <a href="{{ route('integrity.schedules') }}" class="btn btn-outline-secondary"><i class="fas fa-clock me-1"></i>Schedules</a>
    <a href="{{ route('integrity.runs') }}" class="btn btn-outline-secondary"><i class="fas fa-history me-1"></i>Runs</a>
    <a href="{{ route('integrity.holds') }}" class="btn btn-outline-secondary"><i class="fas fa-lock me-1"></i>Holds</a>
    <a href="{{ route('integrity.export') }}" class="btn btn-outline-secondary"><i class="fas fa-download me-1"></i>Export</a>
    <a href="{{ route('integrity.report') }}" class="btn btn-outline-secondary"><i class="fas fa-chart-bar me-1"></i>Report</a>
</div>

@if(isset($results) && !empty($results))
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Latest Check Results</h5></div>
        <div class="card-body">
            <div class="small text-muted mb-3">Run ID: {{ $results['run_id'] ?? '' }} | Started: {{ $results['started_at'] ?? '' }} | Completed: {{ $results['completed_at'] ?? '' }}</div>
            @foreach($results['checks'] ?? [] as $key => $check)
                <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded {{ ($check['passed'] ?? false) ? 'bg-light' : 'bg-danger bg-opacity-10' }}">
                    <i class="fas fa-{{ ($check['passed'] ?? false) ? 'check-circle text-success' : 'times-circle text-danger' }} fa-lg"></i>
                    <div><strong>{{ $check['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}</strong> <span class="badge bg-{{ ($check['count'] ?? 0) > 0 ? 'danger' : 'success' }}">{{ $check['count'] ?? 0 }} issue(s)</span></div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-info">No integrity checks have been run yet. Click "Run Integrity Check" to start.</div>
@endif
@endsection
