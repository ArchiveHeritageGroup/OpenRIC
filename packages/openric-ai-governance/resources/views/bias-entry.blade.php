@extends('theme::layouts.1col')
@section('title', $mode === 'create' ? 'New Bias Entry' : 'Edit Bias Entry')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $mode === 'create' ? 'New' : 'Edit' }} Bias Entry</h1>
    <a href="{{ route('ai-governance.bias-register') }}" class="btn btn-outline-secondary btn-sm">Back to Register</a>
</div>
@include('theme::partials.alerts')

<form method="POST" action="{{ $mode === 'create' ? route('ai-governance.bias-register.store') : route('ai-governance.bias-register.update', $editing->id) }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="card mb-3"><div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="entity_iri" class="form-label">Entity IRI</label>
                <input type="text" class="form-control" id="entity_iri" name="entity_iri" value="{{ old('entity_iri', $editing->entity_iri ?? '') }}" placeholder="Leave blank for collection-level">
            </div>
            <div class="col-md-6 mb-3">
                <label for="collection_iri" class="form-label">Collection IRI</label>
                <input type="text" class="form-control" id="collection_iri" name="collection_iri" value="{{ old('collection_iri', $editing->collection_iri ?? '') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="risk_type" class="form-label">Risk Type <span class="text-danger">*</span></label>
                <select class="form-select" id="risk_type" name="risk_type" required>
                    @foreach($riskTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('risk_type', $editing->risk_type ?? '') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                <select class="form-select" id="severity" name="severity" required>
                    @foreach($severityLevels as $val => $label)
                        <option value="{{ $val }}" @selected(old('severity', $editing->severity ?? 'medium') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="requires_redaction" value="1" id="requires_redaction" @checked(old('requires_redaction', $editing->requires_redaction ?? false))>
                    <label class="form-check-label" for="requires_redaction">Requires Redaction</label>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description', $editing->description ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="specific_content" class="form-label">Specific Content (the actual harmful text)</label>
            <textarea class="form-control" id="specific_content" name="specific_content" rows="2">{{ old('specific_content', $editing->specific_content ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="mitigation_notes" class="form-label">Mitigation Notes</label>
            <textarea class="form-control" id="mitigation_notes" name="mitigation_notes" rows="2">{{ old('mitigation_notes', $editing->mitigation_notes ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="ai_warning" class="form-label">AI Warning (shown to downstream AI consumers)</label>
            <textarea class="form-control" id="ai_warning" name="ai_warning" rows="2">{{ old('ai_warning', $editing->ai_warning ?? '') }}</textarea>
        </div>
    </div></div>

    @if($mode === 'edit' && ($editing->is_resolved ?? false))
    <div class="alert alert-success">
        <strong>Resolved</strong> by user #{{ $editing->resolved_by }} on {{ \Carbon\Carbon::parse($editing->resolved_at)->format('Y-m-d H:i') }}
        @if($editing->resolution_notes)<br>{{ $editing->resolution_notes }}@endif
    </div>
    @endif

    <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Create Entry' : 'Update Entry' }}</button>
    <a href="{{ route('ai-governance.bias-register') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
