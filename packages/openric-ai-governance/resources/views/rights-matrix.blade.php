@extends('theme::layouts.1col')
@section('title', $mode === 'list' ? 'AI Rights Matrix' : ($mode === 'create' ? 'New Restriction' : 'Edit Restriction'))
@section('content')

@if($mode === 'list')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Rights & Restrictions Matrix</h1>
    <div>
        <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm me-1">Dashboard</a>
        <a href="{{ route('ai-governance.rights-matrix.create') }}" class="btn btn-primary btn-sm">New Restriction</a>
    </div>
</div>
@include('theme::partials.alerts')

<form method="GET" action="{{ route('ai-governance.rights-matrix') }}" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="scope" class="form-select form-select-sm">
            <option value="">All scopes</option>
            @foreach($scopeTypes as $val => $label)
                <option value="{{ $val }}" @selected(($filters['scope'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search IRI / notes..." value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Applies To</th>
                <th>Scope</th>
                <th title="AI Allowed">AI</th>
                <th title="Summarisation">Sum</th>
                <th title="Embedding/Indexing">Emb</th>
                <th title="Training Reuse">Trn</th>
                <th title="RAG Retrieval">RAG</th>
                <th title="Translation">Trl</th>
                <th title="Sensitivity Scan">Sen</th>
                <th title="Redaction Required">Red</th>
                <th>Legal Basis</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td><code class="small">{{ \Illuminate\Support\Str::limit($item->applies_to_iri, 40) }}</code></td>
                <td><span class="badge bg-{{ $item->restriction_scope === 'global' ? 'dark' : ($item->restriction_scope === 'collection' ? 'info' : 'secondary') }}">{{ $item->restriction_scope }}</span></td>
                <td>{!! $item->ai_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger fw-bold">N</span>' !!}</td>
                <td>{!! $item->summarisation_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger">N</span>' !!}</td>
                <td>{!! $item->embedding_indexing_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger">N</span>' !!}</td>
                <td>{!! $item->training_reuse_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger">N</span>' !!}</td>
                <td>{!! $item->rag_retrieval_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger">N</span>' !!}</td>
                <td>{!! $item->translation_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger">N</span>' !!}</td>
                <td>{!! $item->sensitivity_scan_allowed ? '<span class="text-success">Y</span>' : '<span class="text-danger">N</span>' !!}</td>
                <td>{!! $item->redaction_required_before_ai ? '<span class="text-warning fw-bold">Y</span>' : '<span class="text-muted">N</span>' !!}</td>
                <td class="small">{{ \Illuminate\Support\Str::limit($item->legal_basis ?? '-', 30) }}</td>
                <td>
                    <a href="{{ route('ai-governance.rights-matrix.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="POST" action="{{ route('ai-governance.rights-matrix.delete', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete this restriction?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Del</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="12" class="text-muted text-center">No restrictions configured.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($total > $limit)
<nav><ul class="pagination pagination-sm">
    @for($p = 1; $p <= ceil($total / $limit); $p++)
        <li class="page-item @if($p === $page) active @endif"><a class="page-link" href="{{ route('ai-governance.rights-matrix', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
    @endfor
</ul></nav>
@endif

{{-- Bulk Apply Section --}}
<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0">Bulk Apply Restrictions</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('ai-governance.rights-matrix.bulk') }}">@csrf
            <div class="mb-3">
                <label for="entity_iris" class="form-label">Entity IRIs (one per line)</label>
                <textarea class="form-control" id="entity_iris" name="entity_iris" rows="4" required placeholder="https://example.org/record/1&#10;https://example.org/record/2"></textarea>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="ai_allowed" value="1" id="bulk_ai" checked><label class="form-check-label" for="bulk_ai">AI Allowed</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="summarisation_allowed" value="1" id="bulk_sum" checked><label class="form-check-label" for="bulk_sum">Summarisation</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="embedding_indexing_allowed" value="1" id="bulk_emb" checked><label class="form-check-label" for="bulk_emb">Embedding</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="training_reuse_allowed" value="1" id="bulk_trn"><label class="form-check-label" for="bulk_trn">Training</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rag_retrieval_allowed" value="1" id="bulk_rag" checked><label class="form-check-label" for="bulk_rag">RAG</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="translation_allowed" value="1" id="bulk_trl" checked><label class="form-check-label" for="bulk_trl">Translation</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sensitivity_scan_allowed" value="1" id="bulk_sen" checked><label class="form-check-label" for="bulk_sen">Sensitivity</label></div></div>
                <div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="redaction_required_before_ai" value="1" id="bulk_red"><label class="form-check-label" for="bulk_red">Redaction Required</label></div></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><input type="text" class="form-control form-control-sm" name="legal_basis" placeholder="Legal basis..."></div>
                <div class="col-md-6 mb-3"><input type="text" class="form-control form-control-sm" name="restriction_notes" placeholder="Notes..."></div>
            </div>
            <button type="submit" class="btn btn-warning" onclick="return confirm('Apply restrictions to all listed entities?')">Bulk Apply</button>
        </form>
    </div>
</div>

@else
{{-- Create / Edit Form --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $mode === 'create' ? 'New' : 'Edit' }} AI Restriction</h1>
    <a href="{{ route('ai-governance.rights-matrix') }}" class="btn btn-outline-secondary btn-sm">Back to Matrix</a>
</div>
@include('theme::partials.alerts')

<form method="POST" action="{{ $mode === 'create' ? route('ai-governance.rights-matrix.store') : route('ai-governance.rights-matrix.update', $editing->id) }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="card mb-3"><div class="card-body">
        <div class="row">
            <div class="col-md-8 mb-3">
                <label for="applies_to_iri" class="form-label">Applies To IRI <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="applies_to_iri" name="applies_to_iri" value="{{ old('applies_to_iri', $editing->applies_to_iri ?? '') }}" required placeholder="Entity or collection IRI, or * for global">
            </div>
            <div class="col-md-4 mb-3">
                <label for="restriction_scope" class="form-label">Scope <span class="text-danger">*</span></label>
                <select class="form-select" id="restriction_scope" name="restriction_scope" required>
                    @foreach($scopeTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('restriction_scope', $editing->restriction_scope ?? 'entity') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <h6 class="mb-3">AI Operations</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="ai_allowed" value="1" id="ai_allowed" @checked(old('ai_allowed', $editing->ai_allowed ?? true))><label class="form-check-label" for="ai_allowed">AI Allowed (master switch)</label></div></div>
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="summarisation_allowed" value="1" id="summarisation_allowed" @checked(old('summarisation_allowed', $editing->summarisation_allowed ?? true))><label class="form-check-label" for="summarisation_allowed">Summarisation</label></div></div>
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="embedding_indexing_allowed" value="1" id="embedding_indexing_allowed" @checked(old('embedding_indexing_allowed', $editing->embedding_indexing_allowed ?? true))><label class="form-check-label" for="embedding_indexing_allowed">Embedding / Indexing</label></div></div>
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="training_reuse_allowed" value="1" id="training_reuse_allowed" @checked(old('training_reuse_allowed', $editing->training_reuse_allowed ?? false))><label class="form-check-label" for="training_reuse_allowed">Training Reuse</label></div></div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rag_retrieval_allowed" value="1" id="rag_retrieval_allowed" @checked(old('rag_retrieval_allowed', $editing->rag_retrieval_allowed ?? true))><label class="form-check-label" for="rag_retrieval_allowed">RAG Retrieval</label></div></div>
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="translation_allowed" value="1" id="translation_allowed" @checked(old('translation_allowed', $editing->translation_allowed ?? true))><label class="form-check-label" for="translation_allowed">Translation</label></div></div>
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sensitivity_scan_allowed" value="1" id="sensitivity_scan_allowed" @checked(old('sensitivity_scan_allowed', $editing->sensitivity_scan_allowed ?? true))><label class="form-check-label" for="sensitivity_scan_allowed">Sensitivity Scan</label></div></div>
            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="redaction_required_before_ai" value="1" id="redaction_required_before_ai" @checked(old('redaction_required_before_ai', $editing->redaction_required_before_ai ?? false))><label class="form-check-label text-warning" for="redaction_required_before_ai">Redaction Required</label></div></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="legal_basis" class="form-label">Legal Basis</label>
                <input type="text" class="form-control" id="legal_basis" name="legal_basis" value="{{ old('legal_basis', $editing->legal_basis ?? '') }}" placeholder="POPIA, GDPR, donor agreement...">
            </div>
            <div class="col-md-4 mb-3">
                <label for="restriction_expires_at" class="form-label">Expires At</label>
                <input type="date" class="form-control" id="restriction_expires_at" name="restriction_expires_at" value="{{ old('restriction_expires_at', $editing->restriction_expires_at ?? '') }}">
            </div>
        </div>
        <div class="mb-3">
            <label for="restriction_notes" class="form-label">Notes</label>
            <textarea class="form-control" id="restriction_notes" name="restriction_notes" rows="2">{{ old('restriction_notes', $editing->restriction_notes ?? '') }}</textarea>
        </div>
    </div></div>
    <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Create Restriction' : 'Update Restriction' }}</button>
    <a href="{{ route('ai-governance.rights-matrix') }}" class="btn btn-secondary">Cancel</a>
</form>
@endif
@endsection
