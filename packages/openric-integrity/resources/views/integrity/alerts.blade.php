@extends('theme::layouts.1col')
@section('title', 'Integrity - Alerts')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Alerts</h1><span class="small text-muted">Integrity alert monitoring</span></div></div>
<div class="card"><div class="card-header"><h5 class="mb-0">Integrity Alerts</h5></div>
<div class="card-body p-0">
    @if(isset($alerts) && count($alerts) > 0)
    <table class="table table-striped table-hover mb-0"><thead><tr><th>Date</th><th>Severity</th><th>Message</th><th>Object</th><th>Status</th></tr></thead>
    <tbody>@foreach($alerts as $a)<tr class="{{ ($a->severity ?? '') === 'critical' ? 'table-danger' : '' }}"><td>{{ $a->created_at ?? '' }}</td><td><span class="badge bg-{{ ($a->severity ?? '') === 'critical' ? 'danger' : (($a->severity ?? '') === 'warning' ? 'warning' : 'info') }}">{{ ucfirst($a->severity ?? 'info') }}</span></td><td>{{ $a->message ?? '' }}</td><td>{{ $a->object_id ?? '' }}</td><td>{{ ucfirst($a->status ?? 'open') }}</td></tr>@endforeach</tbody></table>
    @else<div class="text-center py-4 text-muted">No alerts found.</div>@endif
</div></div>
<div class="mt-3"><a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a></div>
@endsection
