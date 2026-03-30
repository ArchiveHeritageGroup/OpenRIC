@extends('theme::layouts.1col')
@section('title', 'AI Provenance Log')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Output Provenance Log</h1>
    <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>
@include('theme::partials.alerts')

<form method="GET" action="{{ route('ai-governance.provenance-log') }}" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="output_type" class="form-select form-select-sm">
            <option value="">All types</option>
            @foreach($outputTypes as $val => $label)
                <option value="{{ $val }}" @selected(($filters['output_type'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach($statusOptions as $val => $label)
                <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="model_name" class="form-select form-select-sm">
            <option value="">All models</option>
            @foreach($modelNames as $mn)
                <option value="{{ $mn }}" @selected(($filters['model_name'] ?? '') === $mn)>{{ $mn }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}" placeholder="From"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}" placeholder="To"></div>
    <div class="col-md-2"><input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Search..."></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
    <div class="col-auto">
        <div class="form-check form-check-inline mt-1">
            <input class="form-check-input" type="checkbox" name="has_risk_flags" value="1" id="has_risk" @checked(!empty($filters['has_risk_flags']))>
            <label class="form-check-label small" for="has_risk">Risk flags only</label>
        </div>
    </div>
</form>

<p class="text-muted small mb-2">{{ $total }} outputs found</p>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr><th>Entity</th><th>Type</th><th>Model</th><th>Confidence</th><th>Status</th><th>Risk</th><th>Created</th><th>View</th></tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td><code class="small">{{ \Illuminate\Support\Str::limit($item->entity_iri, 35) }}</code></td>
                <td><span class="badge bg-info">{{ $outputTypes[$item->output_type] ?? $item->output_type }}</span></td>
                <td class="small">{{ \Illuminate\Support\Str::limit($item->model_name, 25) }}</td>
                <td>
                    @if($item->confidence_score !== null)
                        <span class="badge bg-{{ $item->confidence_score >= 0.8 ? 'success' : ($item->confidence_score >= 0.5 ? 'warning' : 'danger') }}">{{ number_format($item->confidence_score, 2) }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @php
                        $statusBg = match($item->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'pending_review' => 'warning',
                            'auto_applied' => 'info',
                            'superseded' => 'secondary',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge bg-{{ $statusBg }}">{{ $statusOptions[$item->status] ?? $item->status }}</span>
                </td>
                <td>
                    @if(!empty($item->risk_flags))
                        @foreach($item->risk_flags as $flag)
                            <span class="badge bg-danger">{{ $flag }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="small">{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') }}</td>
                <td><a href="{{ route('ai-governance.provenance-detail', $item->id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-muted text-center">No AI outputs found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($total > $limit)
<nav><ul class="pagination pagination-sm">
    @for($p = 1; $p <= ceil($total / $limit); $p++)
        <li class="page-item @if($p === $page) active @endif"><a class="page-link" href="{{ route('ai-governance.provenance-log', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
    @endfor
</ul></nav>
@endif
@endsection
