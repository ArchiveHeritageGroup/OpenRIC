@extends('theme::layouts.1col')
@section('title', 'AI Evaluation Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Evaluation Dashboard</h1>
    <div>
        <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm me-1">Dashboard</a>
        <a href="{{ route('ai-governance.evaluation.export') }}" class="btn btn-outline-success btn-sm">Export CSV</a>
    </div>
</div>
@include('theme::partials.alerts')

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center h-100"><div class="card-body">
            <h6 class="text-muted">Total Outputs</h6>
            <h3>{{ number_format($summary['total_outputs'] ?? 0) }}</h3>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100"><div class="card-body">
            <h6 class="text-muted">Pending Review</h6>
            <h3 class="text-warning">{{ number_format($summary['pending_reviews'] ?? 0) }}</h3>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100"><div class="card-body">
            <h6 class="text-muted">Approved Today</h6>
            <h3 class="text-success">{{ number_format($summary['approved_today'] ?? 0) }}</h3>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100"><div class="card-body">
            <h6 class="text-muted">Acceptance Rate</h6>
            <h3>{{ $summary['overall_acceptance_rate'] ?? 0 }}%</h3>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100"><div class="card-body">
            <h6 class="text-muted">Avg Confidence</h6>
            <h3>{{ $summary['avg_confidence'] ?? 'N/A' }}</h3>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100"><div class="card-body">
            <h6 class="text-muted">Avg Satisfaction</h6>
            <h3>{{ $summary['avg_satisfaction'] ?? 'N/A' }}<span class="fs-6 text-muted">/5</span></h3>
        </div></div>
    </div>
</div>

{{-- Compute metrics form --}}
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Compute Metrics for Period</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('ai-governance.evaluation.compute') }}" class="row g-2">@csrf
            <div class="col-md-3">
                <select name="use_case" class="form-select form-select-sm" required>
                    <option value="">Select use case...</option>
                    @foreach($outputTypes as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><input type="date" name="period_start" class="form-control form-control-sm" required></div>
            <div class="col-md-3"><input type="date" name="period_end" class="form-control form-control-sm" required></div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Compute & Save</button></div>
        </form>
    </div>
</div>

{{-- Latest metrics per use case --}}
@if(!empty($latestMetrics))
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Latest Metrics by Use Case</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Use Case</th>
                        <th>Period</th>
                        <th>Total</th>
                        <th>Approved</th>
                        <th>Rejected</th>
                        <th>Accept %</th>
                        <th>Avg Edit Dist</th>
                        <th>Avg Confidence</th>
                        <th>Satisfaction</th>
                        <th>Traceability</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($latestMetrics as $uc => $m)
                    <tr>
                        <td><span class="badge bg-info">{{ $outputTypes[$uc] ?? $uc }}</span></td>
                        <td class="small">{{ $m->period_start }} to {{ $m->period_end }}</td>
                        <td>{{ $m->total_outputs }}</td>
                        <td class="text-success">{{ $m->approved_outputs }}</td>
                        <td class="text-danger">{{ $m->rejected_outputs }}</td>
                        <td><strong>{{ $m->acceptance_rate }}%</strong></td>
                        <td>{{ $m->average_edit_distance ?? '-' }}</td>
                        <td>{{ $m->average_confidence !== null ? number_format((float) $m->average_confidence, 3) : '-' }}</td>
                        <td>{{ $m->user_satisfaction_avg ?? '-' }}/5 ({{ $m->user_satisfaction_count }})</td>
                        <td>{{ $m->traceability_score !== null ? number_format((float) $m->traceability_score * 100, 1) . '%' : '-' }}</td>
                        <td><a href="{{ route('ai-governance.evaluation.trend', $uc) }}" class="btn btn-sm btn-outline-info">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Trend data if available --}}
@if(!empty($trend ?? []))
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Trend: {{ $outputTypes[$trendUseCase] ?? $trendUseCase }} (last 12 months)</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Period</th><th>Total</th><th>Approved</th><th>Accept %</th><th>Avg Confidence</th><th>Satisfaction</th></tr></thead>
                <tbody>
                    @foreach($trend as $t)
                    <tr>
                        <td class="small">{{ $t->period_start }} to {{ $t->period_end }}</td>
                        <td>{{ $t->total_outputs }}</td>
                        <td>{{ $t->approved_outputs }}</td>
                        <td>{{ $t->acceptance_rate }}%</td>
                        <td>{{ $t->average_confidence !== null ? number_format((float) $t->average_confidence, 3) : '-' }}</td>
                        <td>{{ $t->user_satisfaction_avg ?? '-' }}/5</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Per-model performance --}}
@if(!empty($modelPerformance))
<div class="card">
    <div class="card-header"><h5 class="mb-0">Per-Model Performance</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Model</th><th>Total</th><th>Approved</th><th>Rejected</th><th>Accept %</th><th>Avg Confidence</th><th>Avg Edit Distance</th><th>Avg Processing (ms)</th></tr></thead>
                <tbody>
                    @foreach($modelPerformance as $mp)
                    <tr>
                        <td><code>{{ $mp->model_name }}</code></td>
                        <td>{{ $mp->total }}</td>
                        <td class="text-success">{{ $mp->approved }}</td>
                        <td class="text-danger">{{ $mp->rejected }}</td>
                        <td><strong>{{ $mp->total > 0 ? number_format(($mp->approved / $mp->total) * 100, 1) : 0 }}%</strong></td>
                        <td>{{ $mp->avg_confidence !== null ? number_format((float) $mp->avg_confidence, 3) : '-' }}</td>
                        <td>{{ $mp->avg_edit_distance !== null ? number_format((float) $mp->avg_edit_distance, 1) : '-' }}</td>
                        <td>{{ $mp->avg_processing_ms !== null ? number_format((float) $mp->avg_processing_ms, 0) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
