@extends('theme::layouts.1col')
@section('title', 'AI Governance Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Governance Dashboard</h1>
</div>
@include('theme::partials.alerts')

<div class="row g-3 mb-4">
    {{-- Module 1: Readiness Profiles --}}
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Readiness Profiles</h6>
                <h2 class="card-title mb-2">{{ $profileCount }}</h2>
                <p class="card-text small text-muted">Collection AI readiness assessments</p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.readiness-profiles') }}" class="btn btn-sm btn-outline-primary">Manage</a></div>
        </div>
    </div>

    {{-- Module 2: Rights Matrix --}}
    <div class="col-md-3">
        <div class="card border-warning h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Rights Restrictions</h6>
                <h2 class="card-title mb-2">{{ $restrictionCount }}</h2>
                <p class="card-text small text-muted">AI use policies configured</p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.rights-matrix') }}" class="btn btn-sm btn-outline-warning">Manage</a></div>
        </div>
    </div>

    {{-- Module 3: Provenance Log --}}
    <div class="col-md-3">
        <div class="card border-info h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">AI Outputs</h6>
                <h2 class="card-title mb-2">{{ $outputCount }}</h2>
                <p class="card-text small text-muted">{{ $pendingCount }} pending review</p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.provenance-log') }}" class="btn btn-sm btn-outline-info">Browse</a></div>
        </div>
    </div>

    {{-- Module 4: Evaluation --}}
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Acceptance Rate</h6>
                <h2 class="card-title mb-2">{{ $evalSummary['overall_acceptance_rate'] ?? 0 }}%</h2>
                <p class="card-text small text-muted">Avg satisfaction: {{ $evalSummary['avg_satisfaction'] ?? 'N/A' }}/5</p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.evaluation') }}" class="btn btn-sm btn-outline-success">Metrics</a></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Module 5: Bias Register --}}
    <div class="col-md-3">
        <div class="card border-danger h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Bias Register</h6>
                <h2 class="card-title mb-2">{{ $biasStats['open'] ?? 0 }} <span class="fs-6 text-muted">open</span></h2>
                <p class="card-text small text-muted">
                    @if(($biasStats['critical'] ?? 0) > 0)
                        <span class="badge bg-danger">{{ $biasStats['critical'] }} critical</span>
                    @endif
                    @if(($biasStats['high'] ?? 0) > 0)
                        <span class="badge bg-warning text-dark">{{ $biasStats['high'] }} high</span>
                    @endif
                    {{ $biasStats['resolved'] ?? 0 }} resolved
                </p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.bias-register') }}" class="btn btn-sm btn-outline-danger">Review</a></div>
        </div>
    </div>

    {{-- Module 6: Derivatives --}}
    <div class="col-md-3">
        <div class="card border-secondary h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Derivatives</h6>
                <h2 class="card-title mb-2">{{ $derivativeStats['current'] ?? 0 }}</h2>
                <p class="card-text small text-muted">
                    @if(!empty($derivativeStats['total_size_bytes']))
                        {{ number_format(($derivativeStats['total_size_bytes'] ?? 0) / 1048576, 1) }} MB total
                    @else
                        No derivatives yet
                    @endif
                </p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.derivatives') }}" class="btn btn-sm btn-outline-secondary">Browse</a></div>
        </div>
    </div>

    {{-- Module 7: Multilingual --}}
    <div class="col-md-3">
        <div class="card border-dark h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Languages Configured</h6>
                <h2 class="card-title mb-2">{{ $langCount }}</h2>
                <p class="card-text small text-muted">Multilingual AI control</p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.multilingual') }}" class="btn btn-sm btn-outline-dark">Configure</a></div>
        </div>
    </div>

    {{-- Module 8: Readiness Checklist --}}
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-1">Readiness Checklists</h6>
                <h2 class="card-title mb-2">{{ $readyChecklists }} / {{ $checklistCount }}</h2>
                <p class="card-text small text-muted">Projects ready for AI</p>
            </div>
            <div class="card-footer bg-transparent"><a href="{{ route('ai-governance.readiness-checklist') }}" class="btn btn-sm btn-outline-primary">Manage</a></div>
        </div>
    </div>
</div>

{{-- Recent activity --}}
@if(!empty($evalSummary['by_output_type']))
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Output Breakdown by Type</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Type</th><th>Total</th><th>Approved</th><th>Rejected</th><th>Pending</th></tr></thead>
                <tbody>
                    @foreach($evalSummary['by_output_type'] as $row)
                        <tr>
                            <td>{{ $row->output_type }}</td>
                            <td>{{ $row->total }}</td>
                            <td><span class="text-success">{{ $row->approved }}</span></td>
                            <td><span class="text-danger">{{ $row->rejected }}</span></td>
                            <td><span class="text-warning">{{ $row->pending }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(!empty($evalSummary['by_model']))
<div class="card">
    <div class="card-header"><h5 class="mb-0">Output Breakdown by Model</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Model</th><th>Total Outputs</th></tr></thead>
                <tbody>
                    @foreach($evalSummary['by_model'] as $row)
                        <tr><td><code>{{ $row->model_name }}</code></td><td>{{ $row->total }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
