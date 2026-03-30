@extends('theme::layouts.1col')
@section('title', ($mode ?? 'list') === 'create' ? 'New Derivative' : 'AI Derivatives')
@section('content')

@if(($mode ?? 'list') === 'list')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Derivative Packaging</h1>
    <div>
        <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm me-1">Dashboard</a>
        <a href="{{ route('ai-governance.derivatives.create') }}" class="btn btn-primary btn-sm">New Derivative</a>
    </div>
</div>
@include('theme::partials.alerts')

{{-- Stats --}}
@if(!empty($derivativeStats))
<div class="row g-2 mb-3">
    <div class="col-auto"><span class="badge bg-dark fs-6">{{ $derivativeStats['total'] ?? 0 }} total</span></div>
    <div class="col-auto"><span class="badge bg-primary fs-6">{{ $derivativeStats['current'] ?? 0 }} current</span></div>
    @if(!empty($derivativeStats['total_size_bytes']))
        <div class="col-auto"><span class="badge bg-info fs-6">{{ number_format(($derivativeStats['total_size_bytes'] ?? 0) / 1048576, 1) }} MB</span></div>
    @endif
</div>
@endif

<form method="GET" action="{{ route('ai-governance.derivatives') }}" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="derivative_type" class="form-select form-select-sm">
            <option value="">All types</option>
            @foreach($derivativeTypes as $val => $label)
                <option value="{{ $val }}" @selected(($filters['derivative_type'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="format" class="form-select form-select-sm">
            <option value="">All formats</option>
            @foreach($derivativeFormats as $val => $label)
                <option value="{{ $val }}" @selected(($filters['format'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="is_current" class="form-select form-select-sm">
            <option value="">All versions</option>
            <option value="1" @selected(($filters['is_current'] ?? '') === '1')>Current only</option>
            <option value="0" @selected(($filters['is_current'] ?? '') === '0')>Superseded</option>
        </select>
    </div>
    <div class="col-md-2"><input type="text" name="language" class="form-control form-control-sm" value="{{ $filters['language'] ?? '' }}" placeholder="Language code"></div>
    <div class="col-md-3"><input type="text" name="entity_iri" class="form-control form-control-sm" value="{{ $filters['entity_iri'] ?? '' }}" placeholder="Entity IRI"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead><tr><th>Entity</th><th>Type</th><th>Format</th><th>Lang</th><th>Size</th><th>Version</th><th>Current</th><th>Tool</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td><code class="small">{{ \Illuminate\Support\Str::limit($item->entity_iri, 30) }}</code></td>
                <td><span class="badge bg-info">{{ $derivativeTypes[$item->derivative_type] ?? $item->derivative_type }}</span></td>
                <td><span class="badge bg-secondary">{{ $derivativeFormats[$item->format] ?? $item->format }}</span></td>
                <td>{{ $item->language ?? '-' }}</td>
                <td class="small">{{ $item->file_size_bytes > 0 ? number_format($item->file_size_bytes / 1024, 1) . ' KB' : '-' }}</td>
                <td>v{{ $item->version }}</td>
                <td>{!! $item->is_current ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                <td class="small">{{ \Illuminate\Support\Str::limit($item->processing_tool ?? '-', 20) }}</td>
                <td class="small">{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d') }}</td>
                <td>
                    <form method="POST" action="{{ route('ai-governance.derivatives.delete', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete this derivative?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Del</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-muted text-center">No derivatives found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($total > $limit)
<nav><ul class="pagination pagination-sm">
    @for($p = 1; $p <= ceil($total / $limit); $p++)
        <li class="page-item @if($p === $page) active @endif"><a class="page-link" href="{{ route('ai-governance.derivatives', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
    @endfor
</ul></nav>
@endif

@else
{{-- Create form --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">New Derivative</h1>
    <a href="{{ route('ai-governance.derivatives') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
</div>
@include('theme::partials.alerts')

<form method="POST" action="{{ route('ai-governance.derivatives.store') }}">@csrf
    <div class="card mb-3"><div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="entity_iri" class="form-label">Entity IRI <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="entity_iri" name="entity_iri" value="{{ old('entity_iri') }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="derivative_type" class="form-label">Type <span class="text-danger">*</span></label>
                <select class="form-select" id="derivative_type" name="derivative_type" required>
                    @foreach($derivativeTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('derivative_type') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="format" class="form-label">Format <span class="text-danger">*</span></label>
                <select class="form-select" id="format" name="format" required>
                    @foreach($derivativeFormats as $val => $label)
                        <option value="{{ $val }}" @selected(old('format') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="file_path" class="form-label">File Path <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="file_path" name="file_path" value="{{ old('file_path') }}" required placeholder="/storage/derivatives/...">
            </div>
            <div class="col-md-3 mb-3">
                <label for="file_size_bytes" class="form-label">File Size (bytes)</label>
                <input type="number" class="form-control" id="file_size_bytes" name="file_size_bytes" value="{{ old('file_size_bytes', 0) }}" min="0">
            </div>
            <div class="col-md-3 mb-3">
                <label for="language" class="form-label">Language</label>
                <input type="text" class="form-control" id="language" name="language" value="{{ old('language') }}" placeholder="en">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="checksum_sha256" class="form-label">SHA-256 Checksum</label>
                <input type="text" class="form-control" id="checksum_sha256" name="checksum_sha256" value="{{ old('checksum_sha256') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="processing_tool" class="form-label">Processing Tool</label>
                <input type="text" class="form-control" id="processing_tool" name="processing_tool" value="{{ old('processing_tool') }}" placeholder="tesseract, tika, custom">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="source_file_path" class="form-label">Source File Path</label>
                <input type="text" class="form-control" id="source_file_path" name="source_file_path" value="{{ old('source_file_path') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="source_checksum_sha256" class="form-label">Source Checksum</label>
                <input type="text" class="form-control" id="source_checksum_sha256" name="source_checksum_sha256" value="{{ old('source_checksum_sha256') }}">
            </div>
        </div>
        <div class="mb-3">
            <label for="processing_notes" class="form-label">Processing Notes</label>
            <textarea class="form-control" id="processing_notes" name="processing_notes" rows="2">{{ old('processing_notes') }}</textarea>
        </div>
    </div></div>
    <button type="submit" class="btn btn-primary">Create Derivative</button>
    <a href="{{ route('ai-governance.derivatives') }}" class="btn btn-secondary">Cancel</a>
</form>
@endif
@endsection
