@extends('theme::layouts.1col')

@section('title', 'AI Evaluation Metrics')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-line me-2"></i>AI Evaluation Metrics</h1>
        <a href="{{ route('ai-governance.metrics.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Record Metric
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Metric Type</th>
                        <th>Records</th>
                        <th>Average Value</th>
                        <th>Latest</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metrics as $metric)
                    <tr>
                        <td>{{ $metricTypes[$metric->metric_type] ?? $metric->metric_type }}</td>
                        <td>{{ $metric->count }}</td>
                        <td>{{ number_format($metric->avg_value, 2) }}%</td>
                        <td>{{ $metric->latest }}</td>
                        <td>
                            <a href="{{ route('ai-governance.metrics.show', $metric->metric_type) }}" class="btn btn-sm btn-outline-primary">Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No metrics recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
