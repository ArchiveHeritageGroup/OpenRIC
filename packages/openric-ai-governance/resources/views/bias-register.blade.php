@extends('theme::layouts.1col')
@section('title', 'Bias Register')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Bias, Harm & Exclusion Register</h1>
    <div>
        <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm me-1">Dashboard</a>
        <a href="{{ route('ai-governance.bias-register.create') }}" class="btn btn-primary btn-sm">New Entry</a>
    </div>
</div>
@include('theme::partials.alerts')

{{-- Stats bar --}}
<div class="row g-2 mb-3">
    <div class="col-auto"><span class="badge bg-dark fs-6">{{ $biasStats['total'] ?? 0 }} total</span></div>
    <div class="col-auto"><span class="badge bg-warning text-dark fs-6">{{ $biasStats['open'] ?? 0 }} open</span></div>
    <div class="col-auto"><span class="badge bg-success fs-6">{{ $biasStats['resolved'] ?? 0 }} resolved</span></div>
    @if(($biasStats['critical'] ?? 0) > 0)
        <div class="col-auto"><span class="badge bg-danger fs-6">{{ $biasStats['critical'] }} critical</span></div>
    @endif
    @if(($biasStats['high'] ?? 0) > 0)
        <div class="col-auto"><span class="badge bg-warning text-dark fs-6">{{ $biasStats['high'] }} high</span></div>
    @endif
</div>

<form method="GET" action="{{ route('ai-governance.bias-register') }}" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="risk_type" class="form-select form-select-sm">
            <option value="">All types</option>
            @foreach($riskTypes as $val => $label)
                <option value="{{ $val }}" @selected(($filters['risk_type'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="severity" class="form-select form-select-sm">
            <option value="">All severities</option>
            @foreach($severityLevels as $val => $label)
                <option value="{{ $val }}" @selected(($filters['severity'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="is_resolved" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="0" @selected(($filters['is_resolved'] ?? '') === '0')>Open</option>
            <option value="1" @selected(($filters['is_resolved'] ?? '') === '1')>Resolved</option>
        </select>
    </div>
    <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Search..."></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead><tr><th>Entity</th><th>Type</th><th>Severity</th><th>Description</th><th>Status</th><th>Flagged</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td><code class="small">{{ \Illuminate\Support\Str::limit($item->entity_iri ?? $item->collection_iri ?? '-', 30) }}</code></td>
                <td><span class="badge bg-secondary">{{ $riskTypes[$item->risk_type] ?? $item->risk_type }}</span></td>
                <td>
                    @php $sevBg = match($item->severity) { 'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'secondary' }; @endphp
                    <span class="badge bg-{{ $sevBg }}">{{ $item->severity }}</span>
                </td>
                <td class="small">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</td>
                <td>
                    @if($item->is_resolved)
                        <span class="badge bg-success">Resolved</span>
                    @else
                        <span class="badge bg-warning text-dark">Open</span>
                    @endif
                </td>
                <td class="small">{{ $item->flagged_at ? \Carbon\Carbon::parse($item->flagged_at)->format('Y-m-d') : '-' }}</td>
                <td>
                    <a href="{{ route('ai-governance.bias-register.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    @if(!$item->is_resolved)
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#resolveModal{{ $item->id }}">Resolve</button>
                    @endif
                    <form method="POST" action="{{ route('ai-governance.bias-register.delete', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Del</button></form>
                </td>
            </tr>

            {{-- Resolve modal --}}
            @if(!$item->is_resolved)
            <div class="modal fade" id="resolveModal{{ $item->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST" action="{{ route('ai-governance.bias-register.resolve', $item->id) }}">@csrf
                    <div class="modal-header"><h5 class="modal-title">Resolve Bias Entry #{{ $item->id }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p><strong>{{ $riskTypes[$item->risk_type] ?? $item->risk_type }}</strong> &mdash; {{ \Illuminate\Support\Str::limit($item->description, 100) }}</p>
                        <div class="mb-3">
                            <label for="resolution_notes_{{ $item->id }}" class="form-label">Resolution Notes <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="resolution_notes_{{ $item->id }}" name="resolution_notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Mark Resolved</button></div>
                    </form>
                </div></div>
            </div>
            @endif
            @empty
            <tr><td colspan="7" class="text-muted text-center">No bias entries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($total > $limit)
<nav><ul class="pagination pagination-sm">
    @for($p = 1; $p <= ceil($total / $limit); $p++)
        <li class="page-item @if($p === $page) active @endif"><a class="page-link" href="{{ route('ai-governance.bias-register', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
    @endfor
</ul></nav>
@endif
@endsection
