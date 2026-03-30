@extends('theme::layouts.1col')
@section('title', 'Integrity - Runs')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Verification Runs</h1></div></div>
<div class="card"><div class="card-header"><h5 class="mb-0">Runs</h5></div>
<div class="card-body p-0">
    @if(isset($items) && count($items) > 0)
    <table class="table table-striped table-hover mb-0"><thead><tr><th>Run ID</th><th>Started</th><th>Completed</th><th>Checks</th></tr></thead>
    <tbody>@foreach($items as $runId => $item)<tr><td><a href="{{ route('integrity.run-detail', $runId) }}">{{ \Illuminate\Support\Str::limit($item['run_id'] ?? $runId, 20) }}</a></td><td>{{ $item['started_at'] ?? '' }}</td><td>{{ $item['completed_at'] ?? '' }}</td><td>{{ count($item['checks'] ?? []) }}</td></tr>@endforeach</tbody></table>
    @else<div class="text-center py-4 text-muted">No verification runs recorded.</div>@endif
</div></div>
<div class="mt-3"><a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a></div>
@endsection
