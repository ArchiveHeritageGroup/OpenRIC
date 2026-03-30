@extends('theme::layouts.1col')

@section('title', 'Condition Reports Administration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Condition Reports Administration</h1>
    <a href="{{ route('condition.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Browse</a>
</div>

@include('theme::partials.alerts')

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center h-100"><div class="card-body py-3"><div class="fs-3 fw-bold">{{ $stats['total_checks'] ?? 0 }}</div><div class="text-muted small">Total Checks</div></div></div></div>
    <div class="col-md-3"><div class="card text-center h-100 border-primary"><div class="card-body py-3"><div class="fs-3 fw-bold text-primary">{{ $stats['total_photos'] ?? 0 }}</div><div class="text-muted small">Photos</div></div></div></div>
    <div class="col-md-3"><div class="card text-center h-100 border-success"><div class="card-body py-3"><div class="fs-3 fw-bold text-success">{{ $stats['annotated_photos'] ?? 0 }}</div><div class="text-muted small">Annotated</div></div></div></div>
    <div class="col-md-3"><div class="card text-center h-100 border-danger"><div class="card-body py-3"><div class="fs-3 fw-bold text-danger">{{ $stats['overdue_assessments'] ?? 0 }}</div><div class="text-muted small">Overdue</div></div></div></div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Condition Breakdown</h5></div>
            <div class="card-body p-0">
                @if(!empty($breakdown))
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Condition</th><th>Label</th><th>Count</th></tr></thead>
                            <tbody>
                                @foreach($breakdown as $item)
                                    <tr><td><span class="badge bg-info">{{ $item->condition_code ?? '' }}</span></td><td>{{ $item->condition_label ?? '' }}</td><td>{{ $item->count ?? 0 }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">No assessments recorded yet.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Recent Checks</h5></div>
            <div class="card-body p-0">
                @if(!empty($recentChecks))
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($recentChecks, 0, 10) as $check)
                            <a href="{{ route('conditions.show', $check->id ?? $check['id']) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ \Illuminate\Support\Str::limit($check->object_iri ?? $check['object_iri'] ?? '-', 50) }}</h6>
                                        <small class="text-muted">{{ $check->condition_label ?? $check['condition_label'] ?? '' }}</small>
                                    </div>
                                    <small class="text-muted">{{ $check->assessed_at ?? $check['assessed_at'] ?? '' }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-4">No recent checks.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Upcoming Assessments</h5></div>
    <div class="card-body p-0">
        @if(!empty($upcoming))
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Object</th><th>Last Condition</th><th>Next Assessment</th><th>Priority</th></tr></thead>
                    <tbody>
                        @foreach($upcoming as $item)
                            <tr>
                                <td><code class="small">{{ \Illuminate\Support\Str::limit($item['object_iri'] ?? '', 50) }}</code></td>
                                <td><span class="badge bg-info">{{ $item['condition_label'] ?? '' }}</span></td>
                                <td>{{ $item['next_assessment_date'] ?? '-' }}</td>
                                <td>{{ $item['conservation_priority'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-4">No upcoming assessments.</div>
        @endif
    </div>
</div>
@endsection
